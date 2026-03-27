package parser

import (
	"bufio"
	"encoding/json"
	"io"
	"strings"
)

// MixedReader handles both JSON and plain text input from go test
type MixedReader struct {
	JSONLines        []string
	NonJSONLines     []string
	CompilationError *CompilationError
}

// CompilationError represents a compilation error if one occurred
type CompilationError struct {
	Package  string
	Messages []string
}

// NewMixedReader creates a new MixedReader and processes the input
func NewMixedReader(reader io.Reader) *MixedReader {
	mr := &MixedReader{}
	scanner := bufio.NewScanner(reader)
	foundErrorHeader := false
	var errorPkg string

	for scanner.Scan() {
		line := scanner.Text()

		// Try to parse as JSON
		if isJSON(line) {
			mr.JSONLines = append(mr.JSONLines, line)
			continue
		}

		// Not JSON - store it
		mr.NonJSONLines = append(mr.NonJSONLines, line)

		// Check for compilation error pattern
		if isErrorHeader(line) {
			foundErrorHeader = true
			errorPkg = extractPackageName(line)
			continue
		}

		// Capture error messages (all non-FAIL lines after header)
		if foundErrorHeader && isErrorMessage(line) {
			if mr.CompilationError == nil {
				mr.CompilationError = &CompilationError{
					Package:  errorPkg,
					Messages: []string{},
				}
			}
			mr.CompilationError.Messages = append(mr.CompilationError.Messages, line)
		}
	}

	// If we found error header but no error message, still create CompilationError
	if foundErrorHeader && mr.CompilationError == nil {
		mr.CompilationError = &CompilationError{
			Package:  errorPkg,
			Messages: []string{},
		}
	}

	return mr
}

// isJSON checks if a line is valid JSON
func isJSON(line string) bool {
	var event TestEvent
	return json.Unmarshal([]byte(line), &event) == nil
}

// isErrorHeader checks if line starts with # (compilation error header)
func isErrorHeader(line string) bool {
	return len(line) > 0 && line[0] == '#'
}

// extractPackageName extracts package name from error header line
func extractPackageName(line string) string {
	if len(line) > 2 {
		return line[2:] // Everything after "# "
	}
	return ""
}

// isErrorMessage checks if line is a valid error message
func isErrorMessage(line string) bool {
	return line != "" && !strings.HasPrefix(line, "FAIL")
}
