package main

import (
	"bufio"
	"bytes"
	"encoding/json"
	"errors"
	"flag"
	"fmt"
	"io"
	"os"
	"path/filepath"
	"strings"

	"github.com/nizos/tdd-guard/reporters/go/internal/formatter"
	tddio "github.com/nizos/tdd-guard/reporters/go/internal/io"
	"github.com/nizos/tdd-guard/reporters/go/internal/parser"
	"github.com/nizos/tdd-guard/reporters/go/internal/storage"
	"github.com/nizos/tdd-guard/reporters/go/internal/transformer"
)

func main() {
	var projectRoot string
	flag.StringVar(&projectRoot, "project-root", "", "Project root directory (absolute path)")
	flag.Parse()

	if err := process(os.Stdin, projectRoot, os.Stdout); err != nil {
		os.Exit(1)
	}
}

func process(input io.Reader, projectRoot string, output io.Writer) error {
	if err := validateProjectRoot(projectRoot); err != nil {
		return err
	}

	// Buffer to collect input
	buffer := &bytes.Buffer{}
	teeReader := tddio.NewTeeReader(input, buffer)

	// Read all input to buffer
	io.ReadAll(teeReader)

	// Format and output
	formatAndOutput(bytes.NewReader(buffer.Bytes()), output)

	// Parse test output from buffer
	mixedReader := parser.NewMixedReader(buffer)
	results, p, err := parseTestResults(mixedReader)
	if err != nil {
		return err
	}

	// Add synthetic test for compilation errors
	if shouldAddCompilationError(results, mixedReader.CompilationError) {
		addCompilationError(results, mixedReader.CompilationError)
	}

	// Transform and save results
	t := transformer.NewTransformer()
	result := t.Transform(results, p, mixedReader.CompilationError)

	s := storage.NewStorage(projectRoot)
	return s.Save(result)
}

func formatAndOutput(input io.Reader, output io.Writer) {
	f := formatter.NewFormatter()
	scanner := bufio.NewScanner(input)

	for scanner.Scan() {
		line := scanner.Text()

		// Try to parse as JSON
		var event parser.TestEvent
		if err := json.Unmarshal([]byte(line), &event); err == nil {
			// It's JSON - format it
			formatted := f.Format(event)
			if formatted != "" {
				fmt.Fprintln(output, formatted)
			}
		} else {
			// Not JSON - pass through as-is
			fmt.Fprintln(output, line)
		}
	}
}

func validateProjectRoot(projectRoot string) error {
	if projectRoot == "" {
		return nil
	}

	if !filepath.IsAbs(projectRoot) {
		return errors.New("project root must be an absolute path")
	}

	cwd, _ := os.Getwd()
	if !strings.HasPrefix(cwd, projectRoot) {
		return errors.New("current directory must be within project root")
	}

	return nil
}

func parseTestResults(mixedReader *parser.MixedReader) (parser.Results, *parser.Parser, error) {
	p := parser.NewParser()

	jsonInput := strings.Join(mixedReader.JSONLines, "\n")
	if jsonInput != "" {
		if err := p.Parse(strings.NewReader(jsonInput)); err != nil {
			return nil, nil, err
		}
	}

	return p.GetResults(), p, nil
}

func shouldAddCompilationError(results parser.Results, compilationError *parser.CompilationError) bool {
	return len(results) == 0 && compilationError != nil
}

func addCompilationError(results parser.Results, compilationError *parser.CompilationError) {
	results[compilationError.Package] = parser.PackageResults{
		"CompilationError": parser.StateFailed,
	}
}
