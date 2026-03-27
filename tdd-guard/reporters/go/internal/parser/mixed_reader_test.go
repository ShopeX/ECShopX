package parser

import (
	"strings"
	"testing"
)

func TestMixedReader_PassesJSONToParser(t *testing.T) {
	input := `{"Action":"pass","Package":"example.com/pkg","Test":"TestExample"}`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if len(mr.JSONLines) != 1 {
		t.Fatalf("Expected 1 JSON line, got %d", len(mr.JSONLines))
	}

	if mr.JSONLines[0] != input {
		t.Errorf("Expected JSON line to be %q, got %q", input, mr.JSONLines[0])
	}
}

func TestMixedReader_ReadsActualInput(t *testing.T) {
	input := `{"Action":"fail","Package":"test/pkg","Test":"TestFail"}`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.JSONLines[0] != input {
		t.Errorf("Expected JSON line to be %q, got %q", input, mr.JSONLines[0])
	}
}

func TestMixedReader_BuffersNonJSONLines(t *testing.T) {
	input := "# command-line-arguments\n"
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if len(mr.NonJSONLines) != 1 {
		t.Fatalf("Expected 1 non-JSON line, got %d", len(mr.NonJSONLines))
	}

	if mr.NonJSONLines[0] != "# command-line-arguments" {
		t.Errorf("Expected non-JSON line to be %q, got %q", "# command-line-arguments", mr.NonJSONLines[0])
	}
}

func TestMixedReader_SeparatesJSONFromNonJSON(t *testing.T) {
	input := `{"Action":"pass","Package":"test"}
# error line
{"Action":"fail","Package":"test2"}`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if len(mr.JSONLines) != 2 {
		t.Fatalf("Expected 2 JSON lines, got %d", len(mr.JSONLines))
	}

	if len(mr.NonJSONLines) != 1 {
		t.Fatalf("Expected 1 non-JSON line, got %d", len(mr.NonJSONLines))
	}

	if mr.NonJSONLines[0] != "# error line" {
		t.Errorf("Expected non-JSON line to be %q, got %q", "# error line", mr.NonJSONLines[0])
	}
}

func TestMixedReader_DetectsCompilationError(t *testing.T) {
	input := `# command-line-arguments
single_import_error_test.go:5:2: no required module provides package github.com/non-existent/module
FAIL	command-line-arguments [setup failed]`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error to be detected")
	}
}

func TestMixedReader_NoCompilationErrorForNormalOutput(t *testing.T) {
	input := `{"Action":"pass","Package":"test","Test":"TestSomething"}`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError != nil {
		t.Fatal("Expected no compilation error for normal JSON output")
	}
}

func TestMixedReader_ExtractsPackageName(t *testing.T) {
	input := `# command-line-arguments
single_import_error_test.go:5:2: no required module provides package github.com/non-existent/module
FAIL	command-line-arguments [setup failed]`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error")
	}
	if mr.CompilationError.Package != "command-line-arguments" {
		t.Errorf("Expected package name %q, got %q", "command-line-arguments", mr.CompilationError.Package)
	}
}

func TestMixedReader_ExtractsDifferentPackageName(t *testing.T) {
	input := `# github.com/example/pkg
error.go:10:5: undefined: SomeFunction`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error")
	}
	if mr.CompilationError.Package != "github.com/example/pkg" {
		t.Errorf("Expected package name %q, got %q", "github.com/example/pkg", mr.CompilationError.Package)
	}
}

func TestMixedReader_GetCompilationErrorMessage(t *testing.T) {
	input := `# command-line-arguments
single_import_error_test.go:5:2: no required module provides package github.com/non-existent/module
FAIL	command-line-arguments [setup failed]`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error")
	}
	expected := "single_import_error_test.go:5:2: no required module provides package github.com/non-existent/module"
	if len(mr.CompilationError.Messages) != 1 || mr.CompilationError.Messages[0] != expected {
		t.Errorf("Expected error message %q, got %v", expected, mr.CompilationError.Messages)
	}
}

func TestMixedReader_GetDifferentCompilationErrorMessage(t *testing.T) {
	input := `# github.com/example/pkg
main.go:10:5: undefined: SomeFunction`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error")
	}
	expected := "main.go:10:5: undefined: SomeFunction"
	if len(mr.CompilationError.Messages) != 1 || mr.CompilationError.Messages[0] != expected {
		t.Errorf("Expected error message %q, got %v", expected, mr.CompilationError.Messages)
	}
}

func TestMixedReader_CapturesMultipleErrorLines(t *testing.T) {
	// Test that multiple error lines are captured
	input := `# example.com/pkg
example.go:9:8: undefined: NewFormatter
example.go:10:12: undefined: TestEvent
{"Action":"fail","Package":"example.com/pkg","Elapsed":0}`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	if mr.CompilationError == nil {
		t.Fatal("Expected compilation error")
	}

	// Should capture both error lines
	if len(mr.CompilationError.Messages) != 2 {
		t.Fatalf("Expected 2 error messages, got %d", len(mr.CompilationError.Messages))
	}

	if mr.CompilationError.Messages[0] != "example.go:9:8: undefined: NewFormatter" {
		t.Errorf("Expected first error message to be 'example.go:9:8: undefined: NewFormatter', got %q", mr.CompilationError.Messages[0])
	}

	if mr.CompilationError.Messages[1] != "example.go:10:12: undefined: TestEvent" {
		t.Errorf("Expected second error message to be 'example.go:10:12: undefined: TestEvent', got %q", mr.CompilationError.Messages[1])
	}
}

func TestMixedReader_HandlesShortLines(t *testing.T) {
	// This could cause a panic if we try to access line[:4] on "OK"
	input := `# pkg
OK`
	reader := strings.NewReader(input)

	mr := NewMixedReader(reader)

	// Should not panic
	if mr.CompilationError != nil {
		// OK is not an error message, just checking it doesn't panic
	}
}
