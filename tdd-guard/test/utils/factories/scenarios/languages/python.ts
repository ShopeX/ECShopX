// Import existing types from contracts
import type { Todo } from '../../../../../src/contracts/schemas/toolSchemas'

const TEST_MODULE_ID = 'src/calculator/test_calculator.py'
const TEST_NAME = 'test_adds_two_numbers'
const TEST_FULL_NAME = 'test_calculator.py::test_adds_two_numbers'

// Test results for Python/pytest
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
                    message: "NameError: name 'Calculator' is not defined",
                    stack:
                      "test_calculator.py:3: in test_adds_two_numbers\n    calculator = Calculator()\nE   NameError: name 'Calculator' is not defined",
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
                    message: "TypeError: 'module' object is not callable",
                    stack:
                      "test_calculator.py:4: in test_adds_two_numbers\n    calculator = Calculator()\nE   TypeError: 'module' object is not callable",
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
                    message: "TypeError: 'int' object is not callable",
                    stack:
                      "test_calculator.py:5: in test_adds_two_numbers\n    result = calculator.add(2, 2)\nE   TypeError: 'int' object is not callable",
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
  attributeError: {
    description: 'attribute error',
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
                    message:
                      "AttributeError: 'Calculator' object has no attribute 'add'",
                    stack:
                      "test_calculator.py:5: in test_adds_two_numbers\n    result = calculator.add(2, 2)\nE   AttributeError: 'Calculator' object has no attribute 'add'",
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
                    message: 'assert 0 == 4',
                    stack:
                      'test_calculator.py:6: in test_adds_two_numbers\n    assert result == 4\nE   assert 0 == 4',
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
            moduleId: 'src/billing/test_domestic.py',
            tests: [
              {
                name: 'test_generates_xml_report',
                fullName: 'test_domestic.py::test_generates_xml_report',
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

// Python test modifications
export const testModifications = {
  singleTest: {
    description: 'adding a single test',
    content: `def test_adds_two_numbers():
    calculator = Calculator()
    result = calculator.add(2, 2)
    assert result == 4
`,
  },
  multipleTests: {
    description: 'adding multiple tests',
    content: `def test_adds_two_numbers():
    calculator = Calculator()
    result = calculator.add(2, 2)
    assert result == 4

def test_subtracts_two_numbers():
    calculator = Calculator()
    result = calculator.subtract(8, 2)
    assert result == 6
`,
  },
  multipleTestsWithImports: {
    description: 'adding multiple tests with imports',
    content: `import pytest
from calculator import Calculator

def test_adds_two_numbers():
    calculator = Calculator()
    result = calculator.add(2, 2)
    assert result == 4

def test_subtracts_two_numbers():
    calculator = Calculator()
    result = calculator.subtract(8, 2)
    assert result == 6
`,
  },
  singleTestWithContainer: {
    description: 'adding a single test with test container',
    content: `class TestCalculator:
    def test_adds_two_numbers(self):
        calculator = Calculator()
        result = calculator.add(2, 2)
        assert result == 4
`,
  },
  singleTestComplete: {
    description: 'adding a complete test file',
    content: `import pytest
from calculator import Calculator

class TestCalculator:
    def test_adds_two_numbers(self):
        calculator = Calculator()
        result = calculator.add(2, 2)
        assert result == 4
`,
  },
  emptyTestContainer: {
    description: 'empty test container',
    content: `class TestCalculator:
    pass
`,
  },
  emptyTestContainerWithImports: {
    description: 'empty test file with imports',
    content: `import pytest
from calculator import Calculator

class TestCalculator:
    pass
`,
  },
  refactoredTests: {
    description: 'refactoring test setup',
    content: `import pytest
from calculator import Calculator

class TestCalculator:
    def setup_method(self):
        self.calculator = Calculator()
    
    def test_adds_two_numbers(self):
        result = self.calculator.add(2, 2)
        assert result == 4
    
    def test_subtracts_two_numbers(self):
        result = self.calculator.subtract(8, 2)
        assert result == 6
`,
  },
} as const

// Python implementation modifications
export const implementationModifications = {
  empty: {
    description: 'file with only a comment',
    content: `# calculator.py
`,
  },
  classStub: {
    description: 'creating empty class stub',
    content: `class Calculator:
    pass
`,
  },
  methodStub: {
    description: 'creating method stub',
    content: `class Calculator:
    def add(self, a, b):
        return None
`,
  },
  methodStubReturning0: {
    description: 'method stub returning 0',
    content: `class Calculator:
    def add(self, a: int, b: int) -> int:
        return 0
`,
  },
  methodImplementation: {
    description: 'implementing method',
    content: `class Calculator:
    def add(self, a: int, b: int) -> int:
        return a + b
`,
  },
  overEngineered: {
    description: 'over-engineering implementation',
    content: `import logging
from typing import Union, Optional
from dataclasses import dataclass
from datetime import datetime

@dataclass
class CalculationResult:
    value: float
    timestamp: datetime
    operation: str

class Calculator:
    def __init__(self):
        self.logger = logging.getLogger(__name__)
        self.history = []
        self._configure_logging()
    
    def _configure_logging(self):
        handler = logging.StreamHandler()
        formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
        handler.setFormatter(formatter)
        self.logger.addHandler(handler)
        self.logger.setLevel(logging.DEBUG)
    
    def _validate_number(self, value: Union[int, float], param_name: str) -> None:
        if not isinstance(value, (int, float)):
            self.logger.error(f"Invalid {param_name}: {value}")
            raise TypeError(f"{param_name} must be a number")
        if not (-1e308 < value < 1e308):
            self.logger.warning(f"{param_name} is very large: {value}")
    
    def add(self, a: Union[int, float], b: Union[int, float]) -> float:
        self.logger.debug(f"Adding {a} + {b}")
        
        # Validate inputs
        self._validate_number(a, "first argument")
        self._validate_number(b, "second argument")
        
        # Perform calculation
        result = a + b
        
        # Store in history
        calc_result = CalculationResult(
            value=result,
            timestamp=datetime.now(),
            operation=f"{a} + {b}"
        )
        self.history.append(calc_result)
        
        self.logger.info(f"Addition result: {result}")
        
        # Check for overflow
        if result == float('inf') or result == float('-inf'):
            self.logger.warning("Result exceeded maximum value")
        
        return result
`,
  },
  completeClass: {
    description: 'implementing multiple methods',
    content: `class Calculator:
    def add(self, a: int, b: int) -> int:
        return a + b
    
    def subtract(self, a: int, b: int) -> int:
        return a - b
    
    def multiply(self, a: int, b: int) -> int:
        return a * b
    
    def divide(self, a: int, b: int) -> float:
        if b == 0:
            raise ValueError("Cannot divide by zero")
        return a / b
`,
  },
} as const

// Todo states (same as TypeScript but with Python context)
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

// Python refactoring test data
export const refactoringImplementation = {
  beforeRefactor: {
    description: 'implementation with repetitive code',
    content: `class Calculator:
    def add(self, a, b):
        if not isinstance(a, (int, float)):
            raise TypeError("First argument must be a number")
        if not isinstance(b, (int, float)):
            raise TypeError("Second argument must be a number")
        return a + b
    
    def subtract(self, a, b):
        if not isinstance(a, (int, float)):
            raise TypeError("First argument must be a number")
        if not isinstance(b, (int, float)):
            raise TypeError("Second argument must be a number")
        return a - b
    
    def multiply(self, a, b):
        if not isinstance(a, (int, float)):
            raise TypeError("First argument must be a number")
        if not isinstance(b, (int, float)):
            raise TypeError("Second argument must be a number")
        return a * b`,
  },
  afterRefactor: {
    description: 'implementation after extracting common validation',
    content: `class Calculator:
    def _validate_numbers(self, a, b):
        if not isinstance(a, (int, float)):
            raise TypeError("First argument must be a number")
        if not isinstance(b, (int, float)):
            raise TypeError("Second argument must be a number")
    
    def add(self, a, b):
        self._validate_numbers(a, b)
        return a + b
    
    def subtract(self, a, b):
        self._validate_numbers(a, b)
        return a - b
    
    def multiply(self, a, b):
        self._validate_numbers(a, b)
        return a * b`,
  },
}

export const refactoringTests = {
  beforeRefactor: {
    description: 'test file with inline assertions',
    content: `import pytest
from calculator import Calculator

class TestCalculator:
    def test_adds_two_numbers(self):
        calc = Calculator()
        assert calc.add(2, 3) == 5
        assert calc.add(-1, 1) == 0
        assert calc.add(0, 0) == 0
    
    def test_subtracts_two_numbers(self):
        calc = Calculator()
        assert calc.subtract(5, 3) == 2
        assert calc.subtract(0, 5) == -5
        assert calc.subtract(-1, -1) == 0
    
    def test_multiplies_two_numbers(self):
        calc = Calculator()
        assert calc.multiply(3, 4) == 12
        assert calc.multiply(-2, 3) == -6
        assert calc.multiply(0, 5) == 0`,
  },
  afterRefactor: {
    description: 'test file after extracting test setup',
    content: `import pytest
from calculator import Calculator

class TestCalculator:
    def setup_method(self):
        self.calc = Calculator()
    
    def test_adds_two_numbers(self):
        assert self.calc.add(2, 3) == 5
        assert self.calc.add(-1, 1) == 0
        assert self.calc.add(0, 0) == 0
    
    def test_subtracts_two_numbers(self):
        assert self.calc.subtract(5, 3) == 2
        assert self.calc.subtract(0, 5) == -5
        assert self.calc.subtract(-1, -1) == 0
    
    def test_multiplies_two_numbers(self):
        assert self.calc.multiply(3, 4) == 12
        assert self.calc.multiply(-2, 3) == -6
        assert self.calc.multiply(0, 5) == 0`,
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
                name: TEST_NAME,
                fullName:
                  'test_calculator.py::TestCalculator::test_adds_two_numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'assert 4 == 5',
                    stack:
                      'test_calculator.py:7: in test_adds_two_numbers\n    assert calc.add(2, 3) == 5\nE   assert 4 == 5',
                    expected: '5',
                    actual: '4',
                  },
                ],
              },
              {
                name: 'test_subtracts_two_numbers',
                fullName:
                  'test_calculator.py::TestCalculator::test_subtracts_two_numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'assert 1 == 2',
                    stack:
                      'test_calculator.py:13: in test_subtracts_two_numbers\n    assert calc.subtract(5, 3) == 2\nE   assert 1 == 2',
                    expected: '2',
                    actual: '1',
                  },
                ],
              },
              {
                name: 'test_multiplies_two_numbers',
                fullName:
                  'test_calculator.py::TestCalculator::test_multiplies_two_numbers',
                state: 'failed',
                errors: [
                  {
                    message: 'assert 11 == 12',
                    stack:
                      'test_calculator.py:19: in test_multiplies_two_numbers\n    assert calc.multiply(3, 4) == 12\nE   assert 11 == 12',
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
                name: TEST_NAME,
                fullName:
                  'test_calculator.py::TestCalculator::test_adds_two_numbers',
                state: 'passed',
                errors: [],
              },
              {
                name: 'test_subtracts_two_numbers',
                fullName:
                  'test_calculator.py::TestCalculator::test_subtracts_two_numbers',
                state: 'passed',
                errors: [],
              },
              {
                name: 'test_multiplies_two_numbers',
                fullName:
                  'test_calculator.py::TestCalculator::test_multiplies_two_numbers',
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
