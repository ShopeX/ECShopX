# 贡献指南（CONTRIBUTING）

感谢你对本项目的兴趣！  
本项目欢迎外部贡献者参与。为确保协作顺畅，请在提交代码前阅读以下说明。

---

## 🧭 外部贡献者提交流程

外部贡献者 **不能直接向主仓库推送代码**。  
所有贡献必须通过 **Fork → 创建分支 → 提交 Pull Request (PR)** 的方式完成。

### 1. Fork 仓库
点击仓库页面右上角的 **Fork**，将本仓库复制到你的 GitHub 账号下。

### 2. 克隆你的 Fork
```bash
git clone https://github.com/<your-username>/<repo-name>.git
cd <repo-name>
```
  
## 添加上游仓库（主仓库）
用于保持你的 Fork 与主仓库同步。
```bash
git remote add upstream https://github.com/ShopeX/ECShopX.git
git fetch upstream
```
  
## 创建功能分支
请不要在 main 直接开发。
```bash
git checkout -b feature/<关联相关 Issue>
```
  
## 完成你的改动
请遵循项目的编码规范与目录结构。

---

### Commit 信息规范
建议使用语义化提交格式：
```
feat: 增加支付接口
fix: 修复结算报错
docs: 更新安装文档
refactor: 优化商品缓存逻辑
test: 增加购物车单元测试
```
Commit 必须清晰且具描述性。
  
### 代码质量要求
提交 PR 前请确保：
* 代码能正常构建
* 通过所有 lint 检查
* 通过所有测试
* 新功能需补充测试用例（如适用）
  
### 保持你的分支为最新
在提交 PR 前，请将你的分支与上游同步，避免冲突：
```bash
git fetch upstream
git merge upstream/main
```
或使用 rebase：
```bash
git fetch upstream
git rebase upstream/main
```
  
### 提交 Pull Request（PR）
进入你的 Fork → 切换到你的分支 → 点击：
  
** Compare & Pull Request
  
提交 PR 时请确保：
* 选择正确的 base 仓库（主仓库）
* 选择正确的 base 分支（如 main 或 dev）
* 填写清晰的标题与说明
* 关联相关 Issue（使用 Closes #123）

### PR 审核流程
你的 PR 会经过以下步骤：
* 自动化检验：构建 / Lint / 测试
* 维护者人工代码审查
* 如有需要，会要求你修改
* 通过后由维护者合并
我们可能会在合并前提出改动建议。

### 以下情况不会被接受
* 直接推送到 main（主仓库已禁用）
* 未讨论即提交的大型改动
* 未通过 CI 检查的 PR
* 包含不相关内容的混合 PR（需拆分）
* 使用有版权争议的代码

### 沟通方式
如果你想提出新功能或报告 Bug，请先提交 Issue 讨论，确认方向一致后再开始编码。

### 感谢贡献
无论大小，每一份贡献都对项目很重要。  
感谢你为本项目付出的时间与努力！