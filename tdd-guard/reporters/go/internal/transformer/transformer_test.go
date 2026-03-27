package transformer

import (
	"strings"
	"testing"

	"github.com/nizos/tdd-guard/reporters/go/internal/parser"
)

// Test constants
const (
	testPackage = "example.com/pkg"
	testName    = "TestExample"
)

func TestTransformer(t *testing.T) {
	t.Run("Creation", func(t *testing.T) {
		t.Run("creates new transformer", func(t *testing.T) {
			transformer := NewTransformer()
			if transformer == nil {
				t.Fatal("Expected transformer to be created")
			}
		})
	})

	t.Run("Transform", func(t *testing.T) {
		t.Run("Empty results", func(t *testing.T) {
			emptyResults := make(parser.Results)
			output := transformResults(t, emptyResults)

			t.Run("returns non-nil output", func(t *testing.T) {
				if output == nil {
					t.Fatal("Expected non-nil output")
				}
			})

			t.Run("initializes testModules", func(t *testing.T) {
				if output.TestModules == nil {
					t.Fatal("Expected TestModules to be initialized")
				}
			})
		})

		t.Run("Module structure", func(t *testing.T) {
			results := createSingleTest(testName, parser.StatePassed)
			output := transformResults(t, results)
			module := getFirstModule(t, output)

			t.Run("creates one module per package", func(t *testing.T) {
				if len(output.TestModules) != 1 {
					t.Fatalf("Expected 1 module, got %d", len(output.TestModules))
				}
			})

			t.Run("sets correct moduleId", func(t *testing.T) {
				if module.ModuleID != testPackage {
					t.Errorf("Expected moduleId %q, got %q", testPackage, module.ModuleID)
				}
			})

			t.Run("initializes tests array", func(t *testing.T) {
				if module.Tests == nil {
					t.Fatal("Expected Tests to be initialized")
				}
			})

			t.Run("creates entry for each test", func(t *testing.T) {
				if len(module.Tests) != 1 {
					t.Fatalf("Expected 1 test, got %d", len(module.Tests))
				}
			})
		})

		t.Run("Test properties", func(t *testing.T) {
			test := transformAndGetFirstTest(t, parser.StatePassed)

			t.Run("sets name correctly", func(t *testing.T) {
				if test.Name != testName {
					t.Errorf("Expected test name %q, got %q", testName, test.Name)
				}
			})

			t.Run("sets fullName with package path", func(t *testing.T) {
				expectedFullName := testPackage + "/" + testName
				if test.FullName != expectedFullName {
					t.Errorf("Expected fullName %q, got %q", expectedFullName, test.FullName)
				}
			})
		})

		t.Run("Test states", func(t *testing.T) {
			testCases := []struct {
				name     string
				input    parser.TestState
				expected string
			}{
				{"passed state", parser.StatePassed, "passed"},
				{"failed state", parser.StateFailed, "failed"},
			}

			for _, tc := range testCases {
				t.Run("maps "+tc.name, func(t *testing.T) {
					test := transformAndGetFirstTest(t, tc.input)
					if test.State != tc.expected {
						t.Errorf("Expected state %q, got %q", tc.expected, test.State)
					}
				})
			}
		})

		t.Run("Error messages", func(t *testing.T) {
			t.Run("failed test has errors field", func(t *testing.T) {
				results := createSingleTest("TestFail", parser.StateFailed)
				output := transformResults(t, results)
				test := getFirstTest(t, output)

				// Check if the Errors field exists (will fail compilation if not)
				if test.Errors == nil {
					// This is OK - errors can be nil if no error message
				}
			})

			t.Run("Transform accepts parser parameter", func(t *testing.T) {
				p := parser.NewParser()
				results := createSingleTest("TestFail", parser.StateFailed)
				transformer := NewTransformer()

				output := transformer.Transform(results, p, nil)
				if output == nil {
					t.Fatal("Expected non-nil output")
				}
			})

			t.Run("includes error message for failed test", func(t *testing.T) {
				// Create a parser with a failed test and output
				p := parser.NewParser()
				input := strings.Join([]string{
					`{"Action":"output","Package":"example.com/pkg","Test":"TestFail","Output":"Expected 6 but got 5\n"}`,
					`{"Action":"fail","Package":"example.com/pkg","Test":"TestFail"}`,
				}, "\n")

				err := p.Parse(strings.NewReader(input))
				if err != nil {
					t.Fatalf("Parse failed: %v", err)
				}

				results := p.GetResults()
				transformer := NewTransformer()
				output := transformer.Transform(results, p, nil)

				test := getFirstTest(t, output)
				if len(test.Errors) == 0 {
					t.Fatal("Expected errors to be populated for failed test with output")
				}
			})
		})

		t.Run("Result reason", func(t *testing.T) {
			t.Run("is always set", func(t *testing.T) {
				results := createSingleTest(testName, parser.StatePassed)
				output := transformResults(t, results)
				if output.Reason == "" {
					t.Fatal("Expected Reason field to be set")
				}
			})

			reasonTestCases := []struct {
				name     string
				tests    parser.PackageResults
				expected string
			}{
				{
					name: "all tests pass",
					tests: parser.PackageResults{
						"TestOne": parser.StatePassed,
						"TestTwo": parser.StatePassed,
					},
					expected: "passed",
				},
				{
					name: "one test fails",
					tests: parser.PackageResults{
						"TestOne": parser.StatePassed,
						"TestTwo": parser.StateFailed,
					},
					expected: "failed",
				},
				{
					name: "all tests fail",
					tests: parser.PackageResults{
						"TestOne": parser.StateFailed,
						"TestTwo": parser.StateFailed,
					},
					expected: "failed",
				},
			}

			for _, tc := range reasonTestCases {
				t.Run(tc.name, func(t *testing.T) {
					results := createMultipleTests(tc.tests)
					output := transformResults(t, results)
					if output.Reason != tc.expected {
						t.Errorf("Expected reason %q, got %q", tc.expected, output.Reason)
					}
				})
			}
		})
	})
}

// Helper functions

func transformResults(t *testing.T, results parser.Results) *TestResult {
	t.Helper()
	transformer := NewTransformer()
	p := parser.NewParser()
	return transformer.Transform(results, p, nil)
}

func createSingleTest(name string, state parser.TestState) parser.Results {
	return parser.Results{
		testPackage: parser.PackageResults{
			name: state,
		},
	}
}

func createMultipleTests(tests parser.PackageResults) parser.Results {
	return parser.Results{
		testPackage: tests,
	}
}

func getFirstModule(t *testing.T, output *TestResult) TestModule {
	t.Helper()
	if len(output.TestModules) == 0 {
		t.Fatal("No modules in output")
	}
	return output.TestModules[0]
}

func getFirstTest(t *testing.T, output *TestResult) Test {
	t.Helper()
	module := getFirstModule(t, output)
	if len(module.Tests) == 0 {
		t.Fatal("No tests in module")
	}
	return module.Tests[0]
}

func transformAndGetFirstTest(t *testing.T, state parser.TestState) Test {
	t.Helper()
	results := createSingleTest(testName, state)
	output := transformResults(t, results)
	return getFirstTest(t, output)
}

func TestTransformer_CompilationErrorWithMultipleMessages(t *testing.T) {
	// Test that multiple compilation error messages are transformed correctly
	results := parser.Results{
		"example.com/pkg": parser.PackageResults{
			"CompilationError": parser.StateFailed,
		},
	}

	compilationError := &parser.CompilationError{
		Package: "example.com/pkg",
		Messages: []string{
			"example.go:9:8: undefined: NewFormatter",
			"example.go:10:12: undefined: TestEvent",
		},
	}

	transformer := NewTransformer()
	output := transformer.Transform(results, parser.NewParser(), compilationError)

	if len(output.TestModules) != 1 {
		t.Fatalf("Expected 1 module, got %d", len(output.TestModules))
	}

	module := output.TestModules[0]
	if len(module.Tests) != 1 {
		t.Fatalf("Expected 1 test, got %d", len(module.Tests))
	}

	test := module.Tests[0]
	if test.Name != "CompilationError" {
		t.Errorf("Expected test name 'CompilationError', got %q", test.Name)
	}

	// Check that we have multiple error entries
	if len(test.Errors) != 2 {
		t.Fatalf("Expected 2 error messages, got %d", len(test.Errors))
	}

	if test.Errors[0].Message != "example.go:9:8: undefined: NewFormatter" {
		t.Errorf("Expected first error message to be 'example.go:9:8: undefined: NewFormatter', got %q", test.Errors[0].Message)
	}

	if test.Errors[1].Message != "example.go:10:12: undefined: TestEvent" {
		t.Errorf("Expected second error message to be 'example.go:10:12: undefined: TestEvent', got %q", test.Errors[1].Message)
	}
}
