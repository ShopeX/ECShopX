# 基于 AI Agent 规则与 TDD（tdd-guard）的使用说明

---

## 1. 配置入口与优先级

| 位置 | 作用 |
|------|------|
| `ECShopX/.cursor/rules/*.mdc` | **始终生效**的规则：`index` → `agent-triggers` → `workflow` → `tdd-guard`（优先级 1～4） |
| `ECShopX/.cursor/agents/*.md` | 各子 Agent 的详细职责与输出约定（如 `planner.md`） |
| `ECShopX/.cursor/hooks.json` + `hooks/cursor-adapter.js` | 将 Cursor 事件转发给 **`tdd-guard` CLI**，在 Edit/Write 等操作前做 TDD 校验 |

---

## 2. Agent 体系（「技能」如何落地）

规则将能力拆成多层角色，**通过 `@角色名` 或自然语言关键词**切换意图；执行层任务在约定中应通过 **`mcp_task` + `subagent_type`** 委派，避免在当前会话里「一人分饰多角」。

| 层级 | 角色 | 要点 |
|------|------|------|
| 规划层 | **Planner** | 访谈、研究（委派 Explore/Librarian）、**强制**咨询 Advisor，产出 `.tasks/plans/{name}.md`；只动 `.tasks/` 下文档，不写业务代码 |
| 规划层 | **Advisor** | 计划前风险与遗漏分析，只读、不改文件 |
| 规划层 | **Reviewer**（可选高精度） | 审查计划质量，返回 `[OKAY]` / `[REJECT]` |
| 编排层 | **Orchestrator** | 用户确认「开始执行」后读计划、拆 TODO、**委派** Developer/Architect/Explore/Librarian，跑验证、维护 Notepad |
| 执行层 | **Developer** | **TDD 实现**（配合 tdd-guard），遵守计划内已审核的测试用例 |
| 执行层 | **Architect** | 架构咨询，只读 |
| 执行层 | **Explore** / **Librarian** | 代码探索 / 文档与最佳实践检索 |

**核心原则（摘自规则）：**

- **先规划后执行**：任何任务（含小修复）都需经 Planner 产出计划；规划者与执行者职责分离。
- **`@<Agent名>` 优先**：用户显式指定角色时优先按该角色行事。
- **子任务必须委派**：Explore、Librarian、Advisor、Reviewer、Developer 等对应工作应用 `mcp_task` 发起，并在 `prompt` 中写清任务（可用下文 6-Section 格式）。

---

## 3. 工作流程速览

### 3.1 规划阶段

1. Planner 访谈并研究代码库（通过委派 Explore/Librarian）。
2. **强制** Advisor 评审后再写计划。
3. 生成 **`.tasks/plans/{name}.md`**，其中必须包含 **Acceptance & Test Cases**：`WHEN/THEN` 验收场景 + 由场景推导的测试用例（含边界）。
4. 可选：高精度模式下由 Reviewer 审查计划；`[REJECT]` 时 Planner 需改计划并再审直至 `[OKAY]`。
5. **用户审核并通过计划中的测试用例清单后**，才允许进入执行阶段。

### 3.2 执行阶段

1. Orchestrator 读取同一 `name` 的计划与 TODO，可维护 **`.tasks/boulder.json`** 与 **`.tasks/notepads/{plan-name}/`**（`learnings.md`、`decisions.md`、`issues.md`、`problems.md` 等，追加写入并带作者与时间戳）。
2. 通过 `mcp_task` 按 TODO 委派；委派 Developer 时 **CONTEXT** 须引用计划中 **Acceptance & Test Cases** 里与本任务相关的条目。
3. **本项目验证**：`phpunit` 零错误、全通过；必要时结合诊断读取变更文件人工核对。

### 3.3 委派用的 6-Section Prompt（Orchestrator → 执行层）

计划要求使用统一结构，便于落地与审计：

- **TASK** / **EXPECTED OUTCOME** / **REQUIRED TOOLS** / **MUST DO** / **MUST NOT DO** / **CONTEXT**

Developer 的 **MUST DO** 中应包含：遵循 TDD 与 `tdd-guard.mdc`；**MUST NOT** 中应包含：不得用脚本或让用户手动改代码来绕过 tdd-guard。

---

## 4. TDD 机制与 tdd-guard

### 4.1 目标

在 **Edit / MultiEdit / Write** 等写文件操作前拦截「跳过测试」或「一次改太多」等行为，引导 **RED → GREEN → REFACTOR**。

### 4.2 运行方式

- `hooks.json` 在 **`preToolUse`**（匹配 Write|Edit|MultiEdit）、**`beforeSubmitPrompt`**、**`sessionStart`** 时执行：`node .cursor/hooks/cursor-adapter.js`。
- **适配器**将 Cursor 的 payload 规范为 `tdd-guard` 所需字段，并在可能时把对已存在文件的 `Write` 转为 `Edit`/`MultiEdit`；最终调用 **`tdd-guard`** 可执行文件并将结果回传。
- 若返回 **block**（或 `continue: false`），适配器以 **退出码 2** 表示拦截。

### 4.3 被拦截时 Agent 应怎么做

1. 阅读返回的 **`reason`**（含违规说明与建议的下一步）。
2. **在本会话内**按建议继续：例如只加最小实现、先 stub、先建空类再跑测试等。
3. **禁止**：用 `sed`/重定向等脚本批量改受检文件，或让用户手动粘贴代码以绕过拦截。

### 4.4 Hook 不可用时的自律

若 tdd-guard 未安装、超时或报错：Developer 仍应按 **同一套 TDD 纪律**（一次一个失败测试、最小实现、全绿再重构）工作，只是没有自动拦截。

### 4.5 Developer 的 TDD 要点

- **RED**：每次只增加一个失败测试，并保留失败证据。
- **GREEN**：最小实现使当前测试通过。
- **REFACTOR**：仅在测试全绿时重构，并持续跑测试。

Planner 在 **TODOs** 中应对涉及实现与测试的任务 **先列写测试（RED），再列实现（GREEN）**，与上述顺序一致。

---

## 5. 实例说明：从需求到一测一实现

以下用**虚构但贴近本仓库**的场景串起整条链路，便于对照规则文件理解「先规划、再审测试用例、再执行」的含义。

**场景：** 为「订单备注」增加接口能力——已登录用户可为自己名下某笔订单设置一段纯文本备注，长度不超过 500 字；非法订单 ID 或越权访问返回约定错误码。

### 在 Cursor 中的具体操作示例

以下假定已用 Cursor **打开本仓库根目录**（即包含 `ECShopX/.cursor/` 的项目根），使规则与钩子对该工作区生效。

**用 `@` 指定角色与上下文（与 `agent-triggers.mdc` 一致）：**

| 写法 | 用途 |
|------|------|
| `@planner` | 显式进入规划流程，先产出 `.tasks/plans/...`，不写业务代码。 |
| `@developer` | 按已确认计划做 TDD 实现。 |
| `@orchestrator` | 在你说「开始执行」后拆任务、委派、跟进验证。 |
| `@advisor` / `@reviewer` 等 | 按需单独咨询顾问或审查（通常由 Planner 流程内委派）。 |
| `@` 文件或文件夹 | 例如 `@routes/...`、`@tests/...`、`.tasks/plans/order-note-api.md`，把实现与验收上下文一并交给模型。 |

**可照抄改写的对话示例：**

1. **启动规划（Chat 输入框）**

   ```text
   @planner 需求：为订单备注增加 API——已登录用户仅可更新本人订单备注，纯文本 ≤500 字，越权与非法订单 ID 返回约定错误码。请先委派 Explore 定位 Order 路由与服务类，再经 Advisor，输出 .tasks/plans/order-note-api.md，含 Acceptance & Test Cases 与测试用例清单；我确认用例后再进入执行。
   ```

2. **把计划文件拉进上下文后追问**

   ```text
   @.tasks/plans/order-note-api.md 请根据其中「越权」相关用例，检查是否还缺边界（如空备注、仅空格）；若缺则更新计划中的测试用例表。
   ```

3. **确认用例后，开始执行**

   ```text
   @orchestrator 我已确认 order-note-api 计划中的测试用例清单。请读取同一计划拆 TODO，并委派 @developer：先按 TDD 实现「订单不属于当前用户则失败」这一条（RED→GREEN），再扩展成功路径。
   ```

4. **在终端跑单测（路径按本机克隆位置调整）**

   ```bash
   ./vendor/bin/phpunit --filter OrderNote
   ```

**若 tdd-guard 拦截某次写入：** 在**同一 Chat 会话**中根据提示继续，例如：

```text
刚才的 Edit 被 tdd-guard 拦截。请阅读返回的 reason，只做最小改动满足当前一条失败测试，不要一次改多个无关文件，不要用脚本批量替换源码。
```

避免新开对话导致丢失计划与钩子上下文；不要用外部脚本或手动粘贴大段代码绕过拦截。

---

**1）规划（Planner）**

- 用户描述需求后，Planner 通过委派 Explore 定位 `Order` 相关路由与服务类，必要时委派 Librarian 查框架约定。
- **强制** 经 Advisor 审视范围与风险后，生成 `.tasks/plans/order-note-api.md`，其中 **Acceptance & Test Cases** 至少包含：
  - `WHEN` 合法用户更新本人订单备注 `THEN` 返回 200 且持久化成功；
  - `WHEN` 订单不属于当前用户 `THEN` 返回 403/业务错误码；
  - `WHEN` 备注超长或空串边界 `THEN` 校验与错误响应符合约定。
- Planner 呈现测试用例清单，**等待你确认**后再进入执行。

**2）可选高精度（Reviewer）**

- 若开启高精度审查，Reviewer 对计划文档做 `[OKAY]` / `[REJECT]`；被拒则 Planner 改计划后再审。

**3）执行（Orchestrator + Developer）**

- 你说「开始执行」后，Orchestrator 读取同一计划中的 TODO，按依赖拆任务，并通过 `mcp_task` 委派 **Developer**，**prompt 采用 6-Section**，且在 **CONTEXT** 中引用计划里与本任务对应的 **验收场景与用例 ID**。
- **Developer** 严格 TDD：例如先写一条「越权访问应失败」的测试（**RED**），再写最小控制器/服务逻辑使该测试通过（**GREEN**），再补「成功路径」测试并实现，最后在全部绿的前提下做小幅重构（**REFACTOR**）。
- 若 **tdd-guard** 拦截某次 Edit/Write，根据返回的 `reason` 在本会话内缩小改动（如只补一个方法、先通过当前单测），**不要**用脚本或让用户手动粘贴绕过。

**4）收尾**

- 运行 `phpunit` 全绿；Orchestrator/Developer 视需要在 `.tasks/notepads/order-note-api/` 追加决策与问题记录。

该实例对应规则中的 **Planner → 计划含 Acceptance & Test Cases → 用户确认 → Orchestrator 委派 → Developer 按 TDD 与 tdd-guard 实现**；真实任务请始终以 `.tasks/plans/{name}.md` 与项目代码为准。

---

## 6. 快速对照：你要做什么时该怎么做

| 场景 | 建议 |
|------|------|
| 新需求或改需求 | 走 Planner → 计划含验收与测试用例 → 用户确认 → Orchestrator 执行 |
| 只想改代码 | 仍应先有对应计划与用例约定（规则要求「无例外先规划」） |
| 写 PHP/API 实现 | Developer 角色 + TDD；接受 tdd-guard 拦截并按 `reason` 调整 |
| 查代码/查文档 | 通过委派 Explore / Librarian，而非在 Planner/Orchestrator 会话里自己搜遍全库代替委派 |

---

*文档生成依据：`ECShopX/.cursor/rules/`、`ECShopX/.cursor/hooks.json`、`ECShopX/.cursor/hooks/cursor-adapter.js`、`ECShopX/.cursor/agents/planner.md` 等；若规则文件后续有更新，请以仓库内原文为准。*
