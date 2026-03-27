// Shared types for scenario data structures
import type { Todo } from '../../../../src/contracts/schemas/toolSchemas'

export interface TestData<T = string> {
  description: string
  content: T
}

// Test result types
export interface TestResults {
  notDefined: TestData
  notAConstructor: TestData
  notAFunction: TestData
  assertionError: TestData
  passing: TestData
  irrelevant: TestData
  empty: TestData
}

// Test modification types
export interface TestModifications {
  singleTest: TestData
  multipleTests: TestData
  multipleTestsWithImports: TestData
  singleTestWithContainer: TestData
  singleTestComplete: TestData
  emptyTestContainer: TestData
  emptyTestContainerWithImports: TestData
  refactoredTests: TestData
}

// Implementation modification types
export interface ImplementationModifications {
  empty: TestData
  classStub: TestData
  methodStub: TestData
  methodStubReturning0: TestData
  methodImplementation: TestData
  overEngineered: TestData
  completeClass: TestData
}

// Todo state types
export interface TodoStates {
  empty: TestData<Todo[]>
  irrelevantCompleted: TestData<Todo[]>
  irrelevantInProgress: TestData<Todo[]>
  classInProgress: TestData<Todo[]>
  methodInProgress: TestData<Todo[]>
  allCompleted: TestData<Todo[]>
  refactoring: TestData<Todo[]>
}

// Refactoring types
export interface RefactoringScenarios {
  beforeRefactor: TestData
  afterRefactor: TestData
}

export interface RefactoringTestResults {
  failing: TestData
  passing: TestData
}

// Language scenario interface
export interface LanguageScenario {
  language: 'typescript' | 'python'
  testFile: string
  implementationFile: string
  testResults: TestResults
  testModifications: TestModifications
  implementationModifications: ImplementationModifications
  todos: TodoStates
  refactoringImplementation: RefactoringScenarios
  refactoringTests: RefactoringScenarios
  refactoringTestResults: RefactoringTestResults
}
