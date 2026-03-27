package formatter

import (
	"fmt"
	"strings"

	"github.com/nizos/tdd-guard/reporters/go/internal/parser"
)

// Formatter converts go test JSON events to standard go test output format.
// It reduces verbose JSON output to concise, human-readable test results.
type Formatter struct {
	handlers map[string]eventHandler
}

type eventHandler func(event parser.TestEvent) string

func NewFormatter() *Formatter {
	f := &Formatter{}
	f.initHandlers()
	return f
}

func (f *Formatter) initHandlers() {
	returnEmpty := func(event parser.TestEvent) string { return "" }

	f.handlers = map[string]eventHandler{
		"start":        returnEmpty,
		"run":          returnEmpty,
		"build-output": f.handleBuildOutput,
		"build-fail":   f.handleBuildFail,
		"output":       f.handleOutput,
		"pass":         f.handlePass,
		"fail":         f.handleFail,
	}
}

func (f *Formatter) Format(event parser.TestEvent) string {
	handler, exists := f.handlers[event.Action]
	if !exists {
		return f.handleUnknown(event)
	}
	return handler(event)
}

func (f *Formatter) handleBuildOutput(event parser.TestEvent) string {
	return trimNewline(event.Output)
}

func (f *Formatter) handleBuildFail(event parser.TestEvent) string {
	if event.Package != "" {
		return fmt.Sprintf("BUILD FAILED\t%s", event.Package)
	}
	return "BUILD FAILED"
}

// handleOutput filters redundant output lines and preserves error messages.
// Removes duplicate package summaries (we generate our own from pass/fail events)
// and test execution markers while keeping actual test failures and error details.
func (f *Formatter) handleOutput(event parser.TestEvent) string {
	output := event.Output

	switch {
	case output == "PASS\n":
		return "" // Filtered - redundant with package pass event
	case output == "FAIL\n":
		return "FAIL"
	case strings.HasPrefix(output, "exit status"):
		return trimNewline(output)
	case strings.HasPrefix(output, "ok  \t"):
		return "" // Filtered - we generate from pass event
	case strings.HasPrefix(output, "FAIL\t"):
		// Keep all FAIL output to preserve error information
		return trimNewline(output)
	}

	if event.Test != "" {
		switch {
		case strings.HasPrefix(output, "=== RUN"), strings.HasPrefix(output, "--- PASS:"):
			return "" // Filter verbose test markers
		case strings.HasPrefix(output, "--- FAIL:"), strings.Contains(output, ":"):
			return trimNewline(output) // Keep failure details and error messages
		default:
			return trimNewline(output)
		}
	}

	if output != "" {
		return trimNewline(output)
	}

	return ""
}

// handlePass generates the "ok" summary line for successful package tests.
// Individual test pass events are filtered to reduce output noise.
func (f *Formatter) handlePass(event parser.TestEvent) string {
	if event.Package != "" && event.Test == "" {
		return fmt.Sprintf("ok  \t%s\t%.3fs", event.Package, event.Elapsed)
	}
	return "" // Filter individual test passes
}

// handleFail generates the "FAIL" summary line for failed package tests.
// Shows build failures with package information for better error visibility.
func (f *Formatter) handleFail(event parser.TestEvent) string {
	if event.Package != "" && event.Test == "" {
		if event.FailedBuild != "" {
			return fmt.Sprintf("FAIL\t%s [build failed]", event.Package)
		}
		return fmt.Sprintf("FAIL\t%s\t%.3fs", event.Package, event.Elapsed)
	}
	// Show individual test failure summaries for better error tracking
	if event.Test != "" {
		return fmt.Sprintf("FAIL\t%s/%s", event.Package, event.Test)
	}
	return ""
}

func (f *Formatter) handleUnknown(event parser.TestEvent) string {
	return fmt.Sprintf("%s: %s", event.Action, trimNewline(event.Output))
}

func trimNewline(s string) string {
	return strings.TrimSuffix(s, "\n")
}
