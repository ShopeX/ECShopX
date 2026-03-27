package formatter

import (
	"testing"

	"github.com/nizos/tdd-guard/reporters/go/internal/parser"
)

func TestFormatter(t *testing.T) {
	t.Run("NewFormatter", func(t *testing.T) {
		formatter := NewFormatter()
		if formatter == nil {
			t.Fatal("Expected NewFormatter to return a formatter instance")
		}
	})

	t.Run("FormatMethodExists", func(t *testing.T) {
		formatter := NewFormatter()
		event := parser.TestEvent{}
		_ = formatter.Format(event)
	})

	t.Run("TestFilterStartEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{Action: "start"})
		if result != "" {
			t.Fatal("Expected start events to be filtered out")
		}
	})

	t.Run("TestFilterRunEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{Action: "run"})
		if result != "" {
			t.Fatal("Expected run events to be filtered out")
		}
	})

	t.Run("TestFilterPASSOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action: "output",
			Output: "PASS\n",
		})
		if result != "" {
			t.Fatalf("Expected PASS output to be filtered, got '%s'", result)
		}
	})

	t.Run("TestPassThroughFAILOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action: "output",
			Output: "FAIL\n",
		})
		if result != "FAIL" {
			t.Fatalf("Expected 'FAIL' but got '%s'", result)
		}
	})

	t.Run("TestPassThroughTestFailureOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action: "output",
			Test:   "TestCalculator/TestShouldAddNumbersCorrectly",
			Output: "    single_failing_test.go:10: Expected 6 but got 5\n",
		})
		expected := "    single_failing_test.go:10: Expected 6 but got 5"
		if result != expected {
			t.Fatalf("Expected '%s' but got '%s'", expected, result)
		}
	})

	t.Run("TestFormatPackagePassSummary", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "pass",
			Package: "command-line-arguments",
			Elapsed: 0.002,
		})
		expected := "ok  \tcommand-line-arguments\t0.002s"
		if result != expected {
			t.Fatalf("Expected '%s' but got '%s'", expected, result)
		}
	})

	t.Run("TestFormatPackageFailSummary", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "fail",
			Package: "command-line-arguments",
			Elapsed: 0.002,
		})
		expected := "FAIL\tcommand-line-arguments\t0.002s"
		if result != expected {
			t.Fatalf("Expected '%s' but got '%s'", expected, result)
		}
	})

	t.Run("TestShowPackageFailWithBuildFailure", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:      "fail",
			Package:     "command-line-arguments",
			Elapsed:     0,
			FailedBuild: "github.com/non-existent/module",
		})
		expected := "FAIL\tcommand-line-arguments [build failed]"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestFilterTestLevelPassEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "pass",
			Package: "command-line-arguments",
			Test:    "TestCalculator",
			Elapsed: 0.001,
		})
		if result != "" {
			t.Fatalf("Expected test-level pass events to be filtered out, got '%s'", result)
		}
	})

	t.Run("TestShowTestLevelFailEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "fail",
			Package: "command-line-arguments",
			Test:    "TestCalculator",
			Elapsed: 0.001,
		})
		expected := "FAIL\tcommand-line-arguments/TestCalculator"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestFilterRunOutputEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "singlePassingTestModule",
			Test:    "TestCalculator",
			Output:  "=== RUN   TestCalculator\n",
		})
		if result != "" {
			t.Fatalf("Expected RUN output events to be filtered out, got '%s'", result)
		}
	})

	t.Run("TestFilterPassOutputEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "singlePassingTestModule",
			Test:    "TestCalculator",
			Output:  "--- PASS: TestCalculator (0.00s)\n",
		})
		if result != "" {
			t.Fatalf("Expected PASS output events to be filtered out, got '%s'", result)
		}
	})

	t.Run("TestShowFailOutputEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "singlePassingTestModule",
			Test:    "TestCalculator",
			Output:  "--- FAIL: TestCalculator (0.00s)\n",
		})
		expected := "--- FAIL: TestCalculator (0.00s)"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestFilterPackageOkOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "singlePassingTestModule",
			Output:  "ok  \tsinglePassingTestModule\t0.002s\n",
		})
		if result != "" {
			t.Fatalf("Expected package ok output to be filtered out, got '%s'", result)
		}
	})

	t.Run("TestShowPackageFailOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "example.com/pkg",
			Output:  "FAIL\texample.com/pkg\t0.002s\n",
		})
		expected := "FAIL\texample.com/pkg\t0.002s"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestPassThroughSetupFailedOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "command-line-arguments",
			Output:  "FAIL\tcommand-line-arguments [setup failed]\n",
		})
		expected := "FAIL\tcommand-line-arguments [setup failed]"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestPassThroughBuildOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action: "build-output",
			Output: "# command-line-arguments\n",
		})
		expected := "# command-line-arguments"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestShowBuildFailEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{Action: "build-fail"})
		expected := "BUILD FAILED"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestPassThroughExitStatusOutput", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "combined",
			Output:  "exit status 1\n",
		})
		expected := "exit status 1"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestPassThroughUnknownOutputEvents", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action:  "output",
			Package: "example",
			Output:  "some unknown output\n",
		})
		expected := "some unknown output"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})

	t.Run("TestPassThroughUnknownActions", func(t *testing.T) {
		result := formatEvent(t, parser.TestEvent{
			Action: "unknown-action",
			Output: "data from unknown action\n",
		})
		expected := "unknown-action: data from unknown action"
		if result != expected {
			t.Fatalf("Expected '%s', got '%s'", expected, result)
		}
	})
}

// Helper functions

func formatEvent(t *testing.T, event parser.TestEvent) string {
	t.Helper()
	formatter := NewFormatter()
	return formatter.Format(event)
}
