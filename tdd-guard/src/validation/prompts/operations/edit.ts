export const EDIT = `## Analyzing Edit Operations

This section shows the code changes being proposed. Compare the old content with the new content to identify what's being added, removed, or modified.

### Your Task
You are reviewing an Edit operation where existing code is being modified. You must determine if this edit violates TDD principles.

**IMPORTANT**: First identify if this is a test file or implementation file by checking the file path for \`.test.\`, \`.spec.\`, or \`test/\`.

### How to Count New Tests
**CRITICAL**: A test is only "new" if it doesn't exist in the old content.

1. **Compare old content vs new content character by character**
   - Find test declarations: \`test(\`, \`it(\`, \`describe(\`
   - A test that exists in both old and new is NOT new
   - Only count tests that appear in new but not in old
   - Count the NUMBER of new tests added, not the total tests in the file

2. **What counts as a new test:**
   - A test block that wasn't in the old content
   - NOT: Moving an existing test to a different location
   - NOT: Renaming an existing test
   - NOT: Reformatting or refactoring existing tests

3. **Multiple test check:**
   - One new test = Allowed (part of TDD cycle)
   - Two or more new tests = Violation

**Example**: If old content has 1 test and new content has 2 tests, that's adding 1 new test (allowed), NOT 2 tests total.

### Analyzing Test File Changes

**For test files**: Adding ONE new test is ALWAYS allowed - no test output required. This is the foundation of TDD.

### Analyzing Implementation File Changes

**For implementation files**:

1. **Check the test output** to understand the current failure
2. **Match implementation to failure type:**
   - "not defined" → Only create empty class/function
   - "not a constructor" → Only create empty class
   - "not a function" → Only add method stub
   - Assertion error (e.g., "expected 0 to be 4") → Implement minimal logic to make it pass
   
3. **Verify minimal implementation:**
   - Don't add extra methods
   - Don't add error handling unless tested
   - Don't implement features beyond current test

### Example Analysis

**Scenario**: Test fails with "Calculator is not defined"
- Allowed: Add \`export class Calculator {}\`
- Violation: Add \`export class Calculator { add(a, b) { return a + b; } }\`
- **Reason**: Should only fix "not defined", not implement methods

`
