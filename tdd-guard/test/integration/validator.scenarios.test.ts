import { describe, test } from 'vitest'
import { validator } from '../../src/validation/validator'
import { Config } from '../../src/config/Config'
import { ModelClientProvider } from '../../src/providers/ModelClientProvider'
import { testData } from '@testUtils'
import { TestData } from '../utils/factories/scenarios/types'
import { Context } from '../../src/contracts/types/Context'
import {
  Todo,
  FileModificationSchema,
} from '../../src/contracts/schemas/toolSchemas'
import { expectDecision } from '../utils/factories/scenarios'

const {
  createWriteOperation,
  createEditOperation,
  createMultiEditOperation,
  languages,
} = testData

export interface Scenario {
  filePath: string
  oldContent?: TestData
  newContent: TestData
  todos: TestData<Todo[]>
  testResult: TestData
  violation: boolean
}

type OperationType = 'Write' | 'Edit' | 'MultiEdit'

describe('Validator', () => {
  const config = new Config({ mode: 'test' })
  const provider = new ModelClientProvider()
  const model = provider.getModelClient(config)
  const defaultOperations: OperationType[] = ['Edit', 'Write']

  languages.forEach((lang) => {
    describe(`${lang.language} scenarios`, () => {
      const testFile = lang.testFile
      const implementationFile = lang.implementationFile
      const {
        testModifications,
        implementationModifications,
        refactoringImplementation,
        refactoringTestResults,
        refactoringTests,
        todos,
        testResults,
      } = lang

      describe('Valid operations', () => {
        const violation = false

        describe('Creating new test', () => {
          const oldContent = testModifications.emptyTestContainerWithImports
          const newContent = testModifications.singleTestComplete

          // Todo states that should not hinder the writing of a new test
          const todoVariations = [
            todos.empty,
            todos.classInProgress,
            todos.irrelevantInProgress,
          ]

          // Test result outputs that should not hinder the writing of a new test
          const resultVariations = [testResults.empty, testResults.irrelevant]

          todoVariations.forEach((todoData) => {
            describe(`with ${todoData.description}`, () => {
              resultVariations.forEach((resultData) => {
                describe(`and ${resultData.description}`, () => {
                  testOperations({
                    filePath: testFile,
                    oldContent,
                    newContent,
                    todos: todoData,
                    testResult: resultData,
                    violation,
                  })
                })
              })
            })
          })
        })

        describe('Creating implementation to pass failing test', () => {
          const oldContent = implementationModifications.methodStubReturning0
          const newContent = implementationModifications.methodImplementation

          // Todo states - any todo state is fine as long as test is failing appropriately
          const todoVariations = [
            todos.empty,
            todos.classInProgress,
            todos.irrelevantInProgress,
          ]

          // Test must be failing for the right reason to allow implementation
          const validFailureResults = [testResults.assertionError]

          todoVariations.forEach((todoData) => {
            describe(`with ${todoData.description}`, () => {
              validFailureResults.forEach((resultData) => {
                describe(`and ${resultData.description}`, () => {
                  testOperations(
                    {
                      filePath: implementationFile,
                      oldContent,
                      newContent,
                      todos: todoData,
                      testResult: resultData,
                      violation,
                    },
                    ['Edit']
                  )
                })
              })
            })
          })
        })

        describe('Refactoring existing tests regardless of todo as long as relevant tests are passing', () => {
          const oldContent = testModifications.multipleTests
          const newContent = testModifications.refactoredTests

          // Test refactoring should be allowed with various todo states
          const todoVariations = [
            todos.empty,
            todos.methodInProgress,
            todos.irrelevantInProgress,
          ]

          // Test refactoring should be allowed only when relevant tests are passing
          const resultVariations = [testResults.passing]

          todoVariations.forEach((todoData) => {
            resultVariations.forEach((resultData) => {
              describe(`with ${todoData.description} and ${resultData.description}`, () => {
                testOperations(
                  {
                    filePath: testFile,
                    oldContent,
                    newContent,
                    todos: todoData,
                    testResult: resultData,
                    violation,
                  },
                  ['Edit']
                )
              })
            })
          })
        })

        describe('Edit operation with one existing test and one new test', () => {
          const oldContent = testModifications.singleTest
          const newContent = testModifications.multipleTests // Contains both the existing test and a new one

          // Various todo states - should be allowed regardless
          const todoVariations = [todos.empty, todos.methodInProgress]

          todoVariations.forEach((todoData) => {
            describe(`with ${todoData.description}`, () => {
              testOperations(
                {
                  filePath: testFile,
                  oldContent,
                  newContent,
                  todos: todoData,
                  testResult: testResults.passing,
                  violation,
                },
                ['Edit']
              )
            })
          })
        })

        describe('Refactoring implementation code', () => {
          describe('Allowed when tests have been run and are passing', () => {
            const oldContent = refactoringImplementation.beforeRefactor
            const newContent = refactoringImplementation.afterRefactor

            const todoVariations = [
              todos.empty,
              todos.irrelevantInProgress,
              todos.refactoring,
            ]

            const resultVariations = [
              refactoringTestResults.passing, // Tests must be passing
            ]

            todoVariations.forEach((todoData) => {
              resultVariations.forEach((resultData) => {
                describe(`with ${todoData.description} and ${resultData.description}`, () => {
                  testOperations(
                    {
                      filePath: implementationFile,
                      oldContent,
                      newContent,
                      todos: todoData,
                      testResult: resultData,
                      violation: false,
                    },
                    ['Edit']
                  )
                })
              })
            })
          })

          describe('Not allowed when no relevant tests are passing', () => {
            const oldContent = refactoringImplementation.beforeRefactor
            const newContent = refactoringImplementation.afterRefactor

            const todoVariations = [
              todos.empty,
              todos.methodInProgress,
              todos.irrelevantInProgress,
              todos.refactoring,
            ]

            const resultVariations = [
              testResults.irrelevant,
              refactoringTestResults.failing,
              testResults.empty,
            ]

            todoVariations.forEach((todoData) => {
              resultVariations.forEach((resultData) => {
                describe(`with ${todoData.description} and ${resultData.description}`, () => {
                  testOperations(
                    {
                      filePath: implementationFile,
                      oldContent,
                      newContent,
                      todos: todoData,
                      testResult: resultData,
                      violation: true,
                    },
                    ['Edit']
                  )
                })
              })
            })
          })
        })

        describe('Refactoring test code', () => {
          describe('Allowed when relevant tests are passing', () => {
            const oldContent = refactoringTests.beforeRefactor
            const newContent = refactoringTests.afterRefactor

            const todoVariations = [
              todos.empty,
              todos.irrelevantInProgress,
              todos.refactoring,
            ]

            const resultVariations = [refactoringTestResults.passing]

            todoVariations.forEach((todoData) => {
              resultVariations.forEach((resultData) => {
                describe(`with ${todoData.description} and ${resultData.description}`, () => {
                  testOperations(
                    {
                      filePath: testFile,
                      oldContent,
                      newContent,
                      todos: todoData,
                      testResult: resultData,
                      violation: false,
                    },
                    ['Edit']
                  )
                })
              })
            })
          })

          describe('Not allowed when no passing tests', () => {
            const oldContent = refactoringTests.beforeRefactor
            const newContent = refactoringTests.afterRefactor

            const todoVariations = [
              todos.empty,
              todos.methodInProgress,
              todos.irrelevantInProgress,
            ]

            const resultVariations = [
              refactoringTestResults.failing,
              testResults.irrelevant,
              testResults.empty,
            ]

            todoVariations.forEach((todoData) => {
              resultVariations.forEach((resultData) => {
                describe(`with ${todoData.description} and ${resultData.description}`, () => {
                  testOperations(
                    {
                      filePath: testFile,
                      oldContent,
                      newContent,
                      todos: todoData,
                      testResult: resultData,
                      violation: true,
                    },
                    ['Edit']
                  )
                })
              })
            })
          })
        })
      })

      describe('TDD Violations', () => {
        const violation = true

        describe('Adding multiple tests at once', () => {
          const oldContent = testModifications.emptyTestContainerWithImports
          const newContent = testModifications.multipleTestsWithImports

          // Todo states - violation occurs regardless of todo state
          const todoVariations = [
            todos.empty,
            todos.classInProgress,
            todos.refactoring,
            todos.irrelevantInProgress,
          ]

          // Test results - violation occurs regardless of test output
          const resultVariations = [
            testResults.empty,
            testResults.passing,
            testResults.assertionError,
            testResults.irrelevant,
          ]

          todoVariations.forEach((todoData) => {
            describe(`with ${todoData.description}`, () => {
              resultVariations.forEach((resultData) => {
                describe(`and ${resultData.description}`, () => {
                  testOperations({
                    filePath: testFile,
                    oldContent,
                    newContent,
                    todos: todoData,
                    testResult: resultData,
                    violation,
                  })
                })
              })
            })
          })
        })

        describe('Implementing without proper test failure', () => {
          const oldContent = implementationModifications.empty
          const newContent = implementationModifications.methodImplementation

          // Todo states - violation occurs regardless of todo state
          const todoVariations = [
            todos.empty,
            todos.classInProgress,
            todos.irrelevantInProgress,
          ]

          // Invalid test results that should block implementation
          const invalidTestResults = [
            testResults.empty, // No test has been run
            testResults.irrelevant, // Test output is for something else
            testResults.notAConstructor, // Failing for wrong reason
            testResults.notDefined, // Failing for wrong reason
          ]

          todoVariations.forEach((todoData) => {
            describe(`with ${todoData.description}`, () => {
              invalidTestResults.forEach((resultData) => {
                describe(`and ${resultData.description}`, () => {
                  testOperations({
                    filePath: implementationFile,
                    oldContent,
                    newContent,
                    todos: todoData,
                    testResult: resultData,
                    violation,
                  })
                })
              })
            })
          })
        })

        describe('Implementing entire class when only one method test is failing', () => {
          const oldContent = implementationModifications.empty
          const newContent = implementationModifications.completeClass // Full class with multiple methods

          // Test is only for add method, but implementation adds everything
          testOperations({
            filePath: implementationFile,
            oldContent,
            newContent,
            todos: todos.methodInProgress,
            testResult: testResults.notDefined, // Single method test failing
            violation,
          })
        })

        describe('Excessive implementation beyond test requirements', () => {
          const filePath = implementationFile
          const oldContent = implementationModifications.methodStub
          const newContent = implementationModifications.overEngineered // Complex implementation with validation, etc.

          // Test only checks basic addition, but implementation adds validation and error handling
          testOperations({
            filePath,
            oldContent,
            newContent,
            todos: todos.methodInProgress,
            testResult: testResults.assertionError, // Simple assertion failure
            violation,
          })
        })
      })

      function testOperations(
        scenario: Scenario,
        operations: OperationType[] = defaultOperations
      ): void {
        operations.forEach((operation) => {
          test(`${operation} should ${scenario.violation ? 'block' : 'allow'}`, async () => {
            const context = createContext(scenario, operation)
            const result = await validator(context, model)
            expectDecision(result, scenario.violation ? 'block' : undefined)
          })
        })
      }
    })
  })
})

// Convert scenario to context for a specific operation type
function createContext(
  scenario: Scenario,
  operationType: OperationType
): Context {
  const modificationString = createModificationString(scenario, operationType)
  validateModification(modificationString)

  return {
    modifications: modificationString,
    todo: formatTodos(scenario.todos.content),
    test: scenario.testResult.content,
  }
}

function createModificationString(
  scenario: Scenario,
  operationType: OperationType
): string {
  const newString = scenario.newContent.content
  const oldString = scenario.oldContent?.content ?? ''

  const operationCreators: Record<OperationType, () => string> = {
    Write: () => createWriteOperation(scenario.filePath, newString),
    Edit: () => createEditOperation(scenario.filePath, oldString, newString),
    MultiEdit: () =>
      createMultiEditOperation(scenario.filePath, [
        {
          old_string: oldString,
          new_string: newString,
          replace_all: false,
        },
      ]),
  }

  return operationCreators[operationType]()
}

function validateModification(modificationString: string): void {
  const validationResult = FileModificationSchema.safeParse(
    JSON.parse(modificationString)
  )

  if (!validationResult.success) {
    throw new Error(
      `Invalid file modification operation: ${validationResult.error.message}`
    )
  }
}

function formatTodos(todoList: unknown[]): string | undefined {
  return todoList.length > 0 ? JSON.stringify(todoList) : undefined
}
