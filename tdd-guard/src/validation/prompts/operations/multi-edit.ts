export const MULTI_EDIT = `## Analyzing MultiEdit Operations

This section shows the code changes being proposed. Compare the old content with the new content to identify what's being added, removed, or modified.

### Your Task
You are reviewing a MultiEdit operation where multiple edits are being applied to the same file. Each edit must be evaluated for TDD compliance.

**FIRST**: Check the file path to identify if this is a test file (\`.test.\`, \`.spec.\`, or \`test/\`) or implementation file.

### How to Analyze Multiple Edits

1. **Process edits sequentially**
   - Each edit builds on the previous one
   - Track cumulative changes across all edits
   - Count total new tests across ALL edits

2. **Counting new tests across edits:**
   - Start with the original file content
   - Apply each edit in sequence
   - Count tests that appear in final result but not in original
   - Multiple new tests across all edits = Violation

3. **Common patterns to watch for:**
   - Edit 1: Adds one test (OK)
   - Edit 2: Adds another test (VIOLATION - 2 total new tests)

### Test File Changes

**For test files**: Adding ONE new test total across all edits is allowed - no test output required. Multiple new tests = violation.
   
### Implementation Changes in MultiEdit

1. **Each edit must be justified**
   - Check if test output supports the change
   - Verify incremental implementation
   - No edit should over-implement

2. **Sequential dependency**
   - Later edits may depend on earlier ones
   - But this doesn't justify multiple new tests
   - Each edit should still follow minimal implementation

### Example MultiEdit Analysis

**Edit 1**: Adds empty Calculator class
- Test output: "Calculator is not defined"
- Analysis: Appropriate minimal fix

**Edit 2**: Adds both add() and subtract() methods
- Test output: "calculator.add is not a function"
- Analysis: VIOLATION - Should only add add() method

**Reason**: "Over-implementation in Edit 2. Test only requires add() method but edit adds both add() and subtract(). Implement only the method causing the test failure."
`
