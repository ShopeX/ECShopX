import * as todoFactory from './factories/todoFactory'
import * as editFactory from './factories/editFactory'
import * as multiEditFactory from './factories/multiEditFactory'
import * as writeFactory from './factories/writeFactory'
import { TEST_DEFAULTS } from './factories/testDefaults'
import * as contextFactory from './factories/contextFactory'
import * as modelClientProviderFactory from './factories/modelClientProviderFactory'
import * as operations from './factories/operations'
import { typescript, python, languages } from './factories/scenarios/index'
import * as reporterFactory from './factories/reporterFactory'
import * as testResultsFactory from './factories/testResultsFactory'
import * as lintFactory from './factories/lintFactory'
import * as userPromptSubmitFactory from './factories/userPromptSubmitFactory'
import * as sessionStartFactory from './factories/sessionStartFactory'

/**
 * Unified test data factory that combines all individual factories
 * Provides a single import point for all test data creation needs
 */
export const testData = {
  // Todo, TodoWrite, and TodoWriteOperation factories
  todo: todoFactory.todo,
  todoWithout: todoFactory.todoWithout,
  todoWrite: todoFactory.todoWrite,
  todoWriteWithout: todoFactory.todoWriteWithout,
  todoWriteOperation: todoFactory.todoWriteOperation,
  todoWriteOperationWithout: todoFactory.todoWriteOperationWithout,
  invalidTodoWriteOperation: todoFactory.invalidTodoWriteOperation,

  // Edit and EditOperation factories
  edit: editFactory.edit,
  editWithout: editFactory.editWithout,
  editOperation: editFactory.editOperation,
  editOperationWithout: editFactory.editOperationWithout,
  invalidEditOperation: editFactory.invalidEditOperation,

  // MultiEdit and MultiEditOperation factories
  multiEdit: multiEditFactory.multiEdit,
  multiEditWithout: multiEditFactory.multiEditWithout,
  multiEditOperation: multiEditFactory.multiEditOperation,
  multiEditOperationWithout: multiEditFactory.multiEditOperationWithout,
  invalidMultiEditOperation: multiEditFactory.invalidMultiEditOperation,

  // Write and WriteOperation factories
  write: writeFactory.write,
  writeWithout: writeFactory.writeWithout,
  writeOperation: writeFactory.writeOperation,
  writeOperationWithout: writeFactory.writeOperationWithout,
  invalidWriteOperation: writeFactory.invalidWriteOperation,

  // ModelClientProvider factories
  modelClientProvider: modelClientProviderFactory.modelClientProvider,

  // Context factories
  context: contextFactory.context,
  contextWithout: contextFactory.contextWithout,

  // Operations - type-safe operation helpers
  createWriteOperation: operations.createWriteOperation,
  createEditOperation: operations.createEditOperation,
  createMultiEditOperation: operations.createMultiEditOperation,

  // Scenarios - test data for integration tests
  testResults: typescript.testResults,
  testModifications: typescript.testModifications,
  implementationModifications: typescript.implementationModifications,
  refactoringImplementation: typescript.refactoringImplementation,
  refactoringTestResults: typescript.refactoringTestResults,
  refactoringTests: typescript.refactoringTests,
  todos: typescript.todos,

  // Language-specific scenarios
  typescript,
  python,
  languages,

  // Reporter factories - Vitest test modules and cases
  testModule: reporterFactory.testModule,
  passedTestCase: reporterFactory.passedTestCase,
  failedTestCase: reporterFactory.failedTestCase,

  // Test results factories
  emptyTestResults: testResultsFactory.emptyTestResults,
  failedTestResults: testResultsFactory.failedTestResults,
  passingTestResults: testResultsFactory.passingTestResults,
  multipleFailedTestResults: testResultsFactory.multipleFailedTestResults,
  mixedTestResults: testResultsFactory.mixedTestResults,
  multipleModulesTestResults: testResultsFactory.multipleModulesTestResults,

  // Test results base builders
  createTestError: testResultsFactory.createTestError,
  createTest: testResultsFactory.createTest,
  createTestModule: testResultsFactory.createTestModule,
  createTestResults: testResultsFactory.createTestResults,
  createUnhandledError: testResultsFactory.createUnhandledError,
  createUnhandledErrorWithout: testResultsFactory.createUnhandledErrorWithout,

  // Lint factories
  lintIssue: lintFactory.lintIssue,
  lintIssueWithout: lintFactory.lintIssueWithout,
  lintResult: lintFactory.lintResult,
  lintResultWithout: lintFactory.lintResultWithout,
  lintData: lintFactory.lintData,
  lintDataWithout: lintFactory.lintDataWithout,
  lintResultWithoutErrors: lintFactory.lintResultWithoutErrors,
  lintResultWithError: lintFactory.lintResultWithError,
  lintDataWithNotificationFlag: lintFactory.lintDataWithNotificationFlag,
  lintDataWithError: lintFactory.lintDataWithError,
  lintDataWithoutErrors: lintFactory.lintDataWithoutErrors,
  eslintMessage: lintFactory.eslintMessage,
  eslintMessageWithout: lintFactory.eslintMessageWithout,
  eslintResult: lintFactory.eslintResult,
  eslintResultWithout: lintFactory.eslintResultWithout,
  golangciLintPosition: lintFactory.golangciLintPosition,
  golangciLintPositionWithout: lintFactory.golangciLintPositionWithout,
  golangciLintIssue: lintFactory.golangciLintIssue,
  golangciLintIssueWithout: lintFactory.golangciLintIssueWithout,
  golangciLintResult: lintFactory.golangciLintResult,
  golangciLintResultWithout: lintFactory.golangciLintResultWithout,

  // UserPromptSubmit factories
  userPromptSubmit: userPromptSubmitFactory.userPromptSubmit,
  userPromptSubmitWithout: userPromptSubmitFactory.userPromptSubmitWithout,

  // SessionStart factories
  sessionStart: sessionStartFactory.sessionStart,
  sessionStartWithout: sessionStartFactory.sessionStartWithout,

  // Default test data values
  defaults: TEST_DEFAULTS,
}
