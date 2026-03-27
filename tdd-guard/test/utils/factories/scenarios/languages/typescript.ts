// Import existing types from contracts
import type { Todo } from '../../../../../src/contracts/schemas/toolSchemas'

const TEST_MODULE_ID = 'src/Calculator/Calculator.test.ts'
const TEST_NAME = 'it adds two numbers'
const TEST_FULL_NAME = 'Calculator > it adds two numbers'

// Test results
export const testResults = {
  notDefined: {
    description: 'not defined error',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: TEST_NAME,
                fullName: TEST_FULL_NAME,
                state: 'failed',
                errors: [
                  {
                    message: 'Calculator is not defined',
                    stack:
                      'ReferenceError: Calculator is not defined\n    at src/Calculator/Calculator.test.ts:3:21',
                  },
                ],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  notAConstructor: {
    description: 'not a constructor error',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: TEST_NAME,
                fullName: TEST_FULL_NAME,
                state: 'failed',
                errors: [
                  {
                    message: 'Calculator is not a constructor',
                    stack:
                      'TypeError: Calculator is not a constructor\n    at src/Calculator/Calculator.test.ts:4:21',
                  },
                ],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  notAFunction: {
    description: 'not a function error',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: TEST_NAME,
                fullName: TEST_FULL_NAME,
                state: 'failed',
                errors: [
                  {
                    message: 'calculator.add is not a function',
                    stack:
                      'TypeError: calculator.add is not a function\n    at src/Calculator/Calculator.test.ts:5:30',
                  },
                ],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  assertionError: {
    description: 'assertion failure',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: TEST_NAME,
                fullName: TEST_FULL_NAME,
                state: 'failed',
                errors: [
                  {
                    message: 'expected +0 to be 4 // Object.is equality',
                    stack:
                      'AssertionError: expected +0 to be 4 // Object.is equality\n    at src/Calculator/Calculator.test.ts:6:23',
                    expected: '4',
                    actual: '0',
                  },
                ],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  passing: {
    description: 'passing tests',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: TEST_NAME,
                fullName: TEST_FULL_NAME,
                state: 'passed',
                errors: [],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  irrelevant: {
    description: 'irrelevant tests',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: 'src/Billing/Domestic.test.ts',
            tests: [
              {
                name: 'it generates xml report',
                fullName: 'DomesticBilling > it generates xml report',
                state: 'passed',
                errors: [],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  empty: {
    description: 'no test output',
    content: ``,
  },
} as const

// Test modifications
export const testModifications = {
  singleTest: {
    description: 'adding a single test',
    content: `
  test('it adds two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })
`,
  },
  multipleTests: {
    description: 'adding multiple tests',
    content: `
  test('it adds two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })

  test('it subtracts two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.subtract(8, 2);
    expect(result).toBe(6);
  })
`,
  },
  multipleTestsWithImports: {
    description: 'adding multiple tests with imports',
    content: `
import { describe, test, expect } from 'vitest'
import { Calculator } from './Calculator'

describe('Calculator', () => {
  test('it adds two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })

  test('it subtracts two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.subtract(8, 2);
    expect(result).toBe(6);
  })
})
`,
  },
  singleTestWithContainer: {
    description: 'adding a single test with test container',
    content: `
describe('Calculator', () => {
  test('it adds two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })
})
`,
  },
  singleTestComplete: {
    description: 'adding a complete test file',
    content: `
import { describe, test, expect } from 'vitest'
import { Calculator } from './Calculator'

describe('Calculator', () => {
  test('it adds two numbers', () => {
    const calculator = new Calculator();
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })
})
`,
  },
  emptyTestContainer: {
    description: 'empty test container',
    content: `
describe('Calculator', () => {

})
`,
  },
  emptyTestContainerWithImports: {
    description: 'empty test file with imports',
    content: `
import { describe, test, expect } from 'vitest'
import { Calculator } from './Calculator'

describe('Calculator', () => {

})
`,
  },
  refactoredTests: {
    description: 'refactoring test setup',
    content: `
  const calculator = new Calculator();

  test('it adds two numbers', () => {
    const result = calculator.add(2, 2);
    expect(result).toBe(4);
  })

  test('it subtracts two numbers', () => {
    const result = calculator.subtract(8, 2);
    expect(result).toBe(6);
  })
`,
  },
} as const

// Implementation modifications
export const implementationModifications = {
  empty: {
    description: 'class file with only a comment',
    content: `// Calculator.ts
`,
  },
  classStub: {
    description: 'creating empty class stub',
    content: `
export class Calculator {}
`,
  },
  methodStub: {
    description: 'creating method stub',
    content: `
export class Calculator {
  add(a: number, b: number) {
    return undefined;
  }
}
`,
  },
  methodStubReturning0: {
    description: 'method stub returning 0',
    content: `
export class Calculator {
  add(a: number, b: number): number {
    return 0;
  }
}
`,
  },
  methodImplementation: {
    description: 'implementing method',
    content: `
export class Calculator {
  add(a: number, b: number): number {
    return a + b;
  }
}
`,
  },
  overEngineered: {
    description: 'over-engineering implementation',
    content: String.raw`
import { Logger } from '../utils/Logger';
import { MetricsCollector } from '../utils/MetricsCollector';
import { ValidationService } from '../services/ValidationService';

export class Calculator {
  private logger: Logger;
  private metrics: MetricsCollector;
  private validationService: ValidationService;

  constructor() {
    this.logger = new Logger('Calculator');
    this.metrics = new MetricsCollector();
    this.validationService = new ValidationService();
  }

  add(a: number, b: number): number {
    this.logger.debug(\`Adding \${a} + \${b}\`);
    
    // Validate inputs
    if (!this.validationService.isValidNumber(a)) {
      this.logger.error(\`Invalid input: \${a}\`);
      throw new Error('First argument must be a valid number');
    }
    if (!this.validationService.isValidNumber(b)) {
      this.logger.error(\`Invalid input: \${b}\`);
      throw new Error('Second argument must be a valid number');
    }
    
    // Track metrics
    const startTime = performance.now();
    this.metrics.incrementCounter('add_operations');
    
    // Perform calculation
    const result = a + b;
    
    // More tracking
    const endTime = performance.now();
    this.metrics.recordTiming('add_operation_duration', endTime - startTime);
    
    // Log result
    this.logger.info(\`Addition result: \${result}\`);
    
    // Check for overflow
    if (!Number.isFinite(result)) {
      this.logger.warn('Result exceeded maximum safe integer');
    }
    
    return result;
  }
}
`,
  },
  completeClass: {
    description: 'implementing multiple methods',
    content: String.raw`
export class Calculator {
  add(a: number, b: number): number {
    return a + b;
  }

  subtract(a: number, b: number): number {
    return a - b;
  }

  multiply(a: number, b: number): number {
    return a * b;
  }

  divide(a: number, b: number): number {
    if (b === 0) {
      throw new Error('Division by zero');
    }
    return a / b;
  }
}
`,
  },
} as const

// Todo states
export const todos = {
  empty: {
    description: 'no todos',
    content: [] as Todo[],
  },
  irrelevantCompleted: {
    description: 'irrelevant completed todos',
    content: [
      {
        content: 'Add support for domestic billing',
        status: 'completed',
        priority: 'high',
        id: '1',
      },
      {
        content: 'Generate XML report tags',
        status: 'completed',
        priority: 'high',
        id: '2',
      },
      {
        content: 'Update billing wiki',
        status: 'completed',
        priority: 'high',
        id: '3',
      },
    ] as Todo[],
  },
  irrelevantInProgress: {
    description: 'irrelevant task in progress',
    content: [
      {
        content: 'Add support for domestic billing',
        status: 'completed',
        priority: 'high',
        id: '1',
      },
      {
        content: 'Generate XML report tags',
        status: 'in_progress',
        priority: 'high',
        id: '2',
      },
      {
        content: 'Update billing wiki',
        status: 'pending',
        priority: 'high',
        id: '3',
      },
    ] as Todo[],
  },
  classInProgress: {
    description: 'class in progress',
    content: [
      {
        content: 'Add Calculator class',
        status: 'in_progress',
        priority: 'high',
        id: '1',
      },
      {
        content: 'Create add method',
        status: 'pending',
        priority: 'high',
        id: '2',
      },
      {
        content: 'Create subtract method',
        status: 'pending',
        priority: 'high',
        id: '3',
      },
    ] as Todo[],
  },
  methodInProgress: {
    description: 'method in progress',
    content: [
      {
        content: 'Add Calculator class',
        status: 'completed',
        priority: 'high',
        id: '1',
      },
      {
        content: 'Create add method',
        status: 'in_progress',
        priority: 'high',
        id: '2',
      },
      {
        content: 'Create subtract method',
        status: 'pending',
        priority: 'high',
        id: '3',
      },
    ] as Todo[],
  },
  allCompleted: {
    description: 'all tasks completed',
    content: [
      {
        content: 'Add Calculator class',
        status: 'completed',
        priority: 'high',
        id: '1',
      },
      {
        content: 'Create add method',
        status: 'completed',
        priority: 'high',
        id: '2',
      },
      {
        content: 'Create subtract method',
        status: 'completed',
        priority: 'high',
        id: '3',
      },
    ] as Todo[],
  },
  refactoring: {
    description: 'refactoring in progress',
    content: [
      {
        content: 'Refactor Calculator class and tests',
        status: 'in_progress',
        priority: 'high',
        id: '1',
      },
    ] as Todo[],
  },
} as const

// Refactoring test data
export const refactoringImplementation = {
  beforeRefactor: {
    description: 'implementation with repetitive code',
    content: `export class Calculator {
  add(a: number, b: number): number {
    if (typeof a !== 'number') {
      throw new Error('First argument must be a number');
    }
    if (typeof b !== 'number') {
      throw new Error('Second argument must be a number');
    }
    return a + b;
  }

  subtract(a: number, b: number): number {
    if (typeof a !== 'number') {
      throw new Error('First argument must be a number');
    }
    if (typeof b !== 'number') {
      throw new Error('Second argument must be a number');
    }
    return a - b;
  }

  multiply(a: number, b: number): number {
    if (typeof a !== 'number') {
      throw new Error('First argument must be a number');
    }
    if (typeof b !== 'number') {
      throw new Error('Second argument must be a number');
    }
    return a * b;
  }
}`,
  },
  afterRefactor: {
    description: 'implementation after extracting common validation',
    content: `export class Calculator {
  private validateNumbers(a: unknown, b: unknown): void {
    if (typeof a !== 'number') {
      throw new Error('First argument must be a number');
    }
    if (typeof b !== 'number') {
      throw new Error('Second argument must be a number');
    }
  }

  add(a: number, b: number): number {
    this.validateNumbers(a, b);
    return a + b;
  }

  subtract(a: number, b: number): number {
    this.validateNumbers(a, b);
    return a - b;
  }

  multiply(a: number, b: number): number {
    this.validateNumbers(a, b);
    return a * b;
  }
}`,
  },
}

export const refactoringTests = {
  beforeRefactor: {
    description: 'test file with inline assertions',
    content: `import { describe, test, expect } from 'vitest'
import { Calculator } from './Calculator'

describe('Calculator', () => {
  test('adds two numbers', () => {
    const calc = new Calculator()
    expect(calc.add(2, 3)).toBe(5)
    expect(calc.add(-1, 1)).toBe(0)
    expect(calc.add(0, 0)).toBe(0)
  })

  test('subtracts two numbers', () => {
    const calc = new Calculator()
    expect(calc.subtract(5, 3)).toBe(2)
    expect(calc.subtract(0, 5)).toBe(-5)
    expect(calc.subtract(-1, -1)).toBe(0)
  })

  test('multiplies two numbers', () => {
    const calc = new Calculator()
    expect(calc.multiply(3, 4)).toBe(12)
    expect(calc.multiply(-2, 3)).toBe(-6)
    expect(calc.multiply(0, 5)).toBe(0)
  })
})`,
  },
  afterRefactor: {
    description: 'test file after extracting test setup',
    content: `import { describe, test, expect, beforeEach } from 'vitest'
import { Calculator } from './Calculator'

describe('Calculator', () => {
  let calc: Calculator

  beforeEach(() => {
    calc = new Calculator()
  })

  test('adds two numbers', () => {
    expect(calc.add(2, 3)).toBe(5)
    expect(calc.add(-1, 1)).toBe(0)
    expect(calc.add(0, 0)).toBe(0)
  })

  test('subtracts two numbers', () => {
    expect(calc.subtract(5, 3)).toBe(2)
    expect(calc.subtract(0, 5)).toBe(-5)
    expect(calc.subtract(-1, -1)).toBe(0)
  })

  test('multiplies two numbers', () => {
    expect(calc.multiply(3, 4)).toBe(12)
    expect(calc.multiply(-2, 3)).toBe(-6)
    expect(calc.multiply(0, 5)).toBe(0)
  })
})`,
  },
}

export const refactoringTestResults = {
  failing: {
    description: 'multiple tests failing',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: 'adds two numbers',
                fullName: 'Calculator > adds two numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'expected 4 to be 5',
                    stack:
                      'AssertionError: expected 4 to be 5\n    at src/Calculator/Calculator.test.ts:10:23',
                    expected: '5',
                    actual: '4',
                  },
                ],
              },
              {
                name: 'subtracts two numbers',
                fullName: 'Calculator > subtracts two numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'expected 1 to be 2',
                    stack:
                      'AssertionError: expected 1 to be 2\n    at src/Calculator/Calculator.test.ts:16:23',
                    expected: '2',
                    actual: '1',
                  },
                ],
              },
              {
                name: 'multiplies two numbers',
                fullName: 'Calculator > multiplies two numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'expected 11 to be 12',
                    stack:
                      'AssertionError: expected 11 to be 12\n    at src/Calculator/Calculator.test.ts:22:23',
                    expected: '12',
                    actual: '11',
                  },
                ],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
  passing: {
    description: 'all tests passing',
    content: JSON.stringify(
      {
        testModules: [
          {
            moduleId: TEST_MODULE_ID,
            tests: [
              {
                name: 'adds two numbers',
                fullName: 'Calculator > adds two numbers',
                state: 'passed',
                errors: [],
              },
              {
                name: 'subtracts two numbers',
                fullName: 'Calculator > subtracts two numbers',
                state: 'passed',
                errors: [],
              },
              {
                name: 'multiplies two numbers',
                fullName: 'Calculator > multiplies two numbers',
                state: 'passed',
                errors: [],
              },
            ],
          },
        ],
      },
      null,
      2
    ),
  },
}
