import { describe, test, expect } from 'vitest'
import {
  TestErrorSchema,
  TestSchema,
  TestModuleSchema,
  TestResultSchema,
  UnhandledErrorSchema,
  isTestModule,
  isTestCase,
  isFailingTest,
  isPassingTest,
  isTestPassing,
} from './reporterSchemas'
import { testData } from '@testUtils'

describe('Reporter schemas', () => {
  describe('UnhandledErrorSchema', () => {
    test.each([
      {
        description: 'without name',
        unhandledError: testData.createUnhandledErrorWithout(['name']),
        expectedSuccess: false,
      },
      {
        description: 'without message',
        unhandledError: testData.createUnhandledErrorWithout(['message']),
        expectedSuccess: false,
      },
      {
        description: 'with all required fields',
        unhandledError: testData.createUnhandledError(),
        expectedSuccess: true,
      },
      {
        description: 'with name and message only',
        unhandledError: testData.createUnhandledError({ stack: undefined }),
        expectedSuccess: true,
      },
      {
        description: 'with custom error name',
        unhandledError: testData.createUnhandledError({
          name: 'TypeError',
          message: 'Cannot read property of undefined',
        }),
        expectedSuccess: true,
      },
    ])('$description', ({ unhandledError, expectedSuccess }) => {
      const result = UnhandledErrorSchema.safeParse(unhandledError)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(unhandledError)
      }
    })
  })

  describe('TestErrorSchema', () => {
    test.each([
      {
        description: 'without message',
        testError: testData.createTestError({ message: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'with message only',
        testError: testData.createTestError({ message: 'test error' }),
        expectedSuccess: true,
      },
      {
        description: 'with message and stack',
        testError: testData.createTestError({
          message: 'test error',
          stack: 'Error: test error\n    at test.ts:1:1',
        }),
        expectedSuccess: true,
      },
    ])('$description', ({ testError, expectedSuccess }) => {
      const result = TestErrorSchema.safeParse(testError)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(testError)
      }
    })
  })

  describe('TestSchema', () => {
    test.each([
      {
        description: 'without name',
        test: testData.createTest({ name: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'without fullName',
        test: testData.createTest({ fullName: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'without state',
        test: testData.createTest({ state: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'with passed state',
        test: testData.createTest({ state: 'passed' }),
        expectedSuccess: true,
      },
      {
        description: 'with failed state and errors',
        test: testData.createTest({
          state: 'failed',
          errors: [testData.createTestError()],
        }),
        expectedSuccess: true,
      },
    ])('$description', ({ test: testCase, expectedSuccess }) => {
      const result = TestSchema.safeParse(testCase)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(testCase)
      }
    })
  })

  describe('TestModuleSchema', () => {
    test.each([
      {
        description: 'without moduleId',
        testModule: testData.createTestModule({ moduleId: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'without tests',
        testModule: testData.createTestModule({ tests: undefined }),
        expectedSuccess: false,
      },
      {
        description: 'with valid module',
        testModule: testData.createTestModule({
          moduleId: '/src/test.ts',
          tests: [testData.createTest()],
        }),
        expectedSuccess: true,
      },
    ])('$description', ({ testModule, expectedSuccess }) => {
      const result = TestModuleSchema.safeParse(testModule)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(testModule)
      }
    })
  })

  describe('TestResultSchema', () => {
    test.each([
      {
        description: 'without testModules',
        jsonTestResult: { someOtherField: 'value' },
        expectedSuccess: false,
      },
      {
        description: 'with empty testModules array',
        jsonTestResult: testData.createTestResults({ testModules: [] }),
        expectedSuccess: true,
      },
      {
        description: 'with valid test result',
        jsonTestResult: testData.createTestResults({
          testModules: [testData.createTestModule()],
        }),
        expectedSuccess: true,
      },
      {
        description: 'with unhandledErrors field',
        jsonTestResult: {
          testModules: [],
          unhandledErrors: [testData.createUnhandledError()],
        },
        expectedSuccess: true,
      },
      {
        description: 'with reason field as "passed"',
        jsonTestResult: {
          testModules: [],
          reason: 'passed',
        },
        expectedSuccess: true,
      },
      {
        description: 'with reason field as "failed"',
        jsonTestResult: {
          testModules: [],
          reason: 'failed',
        },
        expectedSuccess: true,
      },
      {
        description: 'with reason field as "interrupted"',
        jsonTestResult: {
          testModules: [],
          reason: 'interrupted',
        },
        expectedSuccess: true,
      },
      {
        description: 'with invalid reason value',
        jsonTestResult: {
          testModules: [],
          reason: 'invalid',
        },
        expectedSuccess: false,
      },
    ])('$description', ({ jsonTestResult, expectedSuccess }) => {
      const result = TestResultSchema.safeParse(jsonTestResult)
      expect(result.success).toBe(expectedSuccess)

      if (expectedSuccess && result.success) {
        expect(result.data).toEqual(jsonTestResult)
      }
    })
  })

  describe('isTestModule', () => {
    test('returns true for valid test module', () => {
      const validModule = testData.createTestModule()
      expect(isTestModule(validModule)).toBe(true)
    })

    test('returns false for object missing moduleId', () => {
      const invalidModule = { tests: [] }
      expect(isTestModule(invalidModule)).toBe(false)
    })

    test('returns false for object missing tests array', () => {
      const invalidModule = { moduleId: '/src/test.ts' }
      expect(isTestModule(invalidModule)).toBe(false)
    })

    test('returns false for primitive values', () => {
      expect(isTestModule('string')).toBe(false)
      expect(isTestModule(123)).toBe(false)
      expect(isTestModule(null)).toBe(false)
      expect(isTestModule(undefined)).toBe(false)
    })
  })

  describe('isTestCase', () => {
    test('returns true for valid test case', () => {
      const validTest = testData.createTest()
      expect(isTestCase(validTest)).toBe(true)
    })

    test('returns false for object missing name', () => {
      const invalidTest = { fullName: 'test', state: 'passed' }
      expect(isTestCase(invalidTest)).toBe(false)
    })

    test('returns false for object with invalid state', () => {
      const invalidTest = { name: 'test', fullName: 'test', state: 'invalid' }
      expect(isTestCase(invalidTest)).toBe(false)
    })

    test('returns false for primitive values', () => {
      expect(isTestCase('string')).toBe(false)
      expect(isTestCase(null)).toBe(false)
    })
  })

  describe('isFailingTest', () => {
    test('returns true for test with failed state', () => {
      const failedTest = testData.createTest({ state: 'failed' })
      expect(isFailingTest(failedTest)).toBe(true)
    })

    test('returns false for test with passed state', () => {
      const passedTest = testData.createTest({ state: 'passed' })
      expect(isFailingTest(passedTest)).toBe(false)
    })

    test('returns false for test with skipped state', () => {
      const skippedTest = testData.createTest({ state: 'skipped' })
      expect(isFailingTest(skippedTest)).toBe(false)
    })

    test('returns false for invalid test object', () => {
      const invalidTest = { name: 'test', state: 'failed' }
      expect(isFailingTest(invalidTest)).toBe(false)
    })

    test('returns false for non-test values', () => {
      expect(isFailingTest('string')).toBe(false)
      expect(isFailingTest(null)).toBe(false)
    })
  })

  describe('isPassingTest', () => {
    test('returns true for test with passed state', () => {
      const passedTest = testData.createTest({ state: 'passed' })
      expect(isPassingTest(passedTest)).toBe(true)
    })

    test('returns false for test with failed state', () => {
      const failedTest = testData.createTest({ state: 'failed' })
      expect(isPassingTest(failedTest)).toBe(false)
    })

    test('returns false for test with skipped state', () => {
      const skippedTest = testData.createTest({ state: 'skipped' })
      expect(isPassingTest(skippedTest)).toBe(false)
    })

    test('returns false for invalid test object', () => {
      const invalidTest = { name: 'test', state: 'passed' }
      expect(isPassingTest(invalidTest)).toBe(false)
    })

    test('returns false for non-test values', () => {
      expect(isPassingTest('string')).toBe(false)
      expect(isPassingTest(null)).toBe(false)
    })
  })

  describe('isTestPassing', () => {
    describe('with test arrays', () => {
      const passingTest = testData.createTest({ state: 'passed' })
      const failingTest = testData.createTest({ state: 'failed' })

      test.each([
        {
          description: 'returns true when all tests pass',
          tests: [passingTest, passingTest],
          expected: true,
        },
        {
          description: 'returns false when any test fails',
          tests: [passingTest, failingTest],
          expected: false,
        },
        {
          description: 'returns true when single test passes',
          tests: [passingTest],
          expected: true,
        },
        {
          description: 'returns false when single test fails',
          tests: [failingTest],
          expected: false,
        },
        {
          description: 'returns false when multiple tests fail',
          tests: [failingTest, failingTest],
          expected: false,
        },
        {
          description: 'returns false when test modules have no tests',
          tests: [],
          expected: false,
        },
      ])('$description', ({ tests, expected }) => {
        const testResult = testData.createTestResults({
          testModules: [testData.createTestModule({ tests })],
        })
        expect(isTestPassing(testResult)).toBe(expected)
      })
    })

    test('returns false when there are no test modules', () => {
      const testResult = testData.createTestResults({
        testModules: [],
      })
      expect(isTestPassing(testResult)).toBe(false)
    })
  })
})
