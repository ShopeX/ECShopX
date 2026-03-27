package io

import (
	"bytes"
	"io"
	"strings"
	"testing"
)

func TestTeeReader(t *testing.T) {
	input := "test data"

	t.Run("reads input", func(t *testing.T) {
		result, _ := readThroughTeeReader(input)

		if string(result) != input {
			t.Errorf("Expected to read '%s', got '%s'", input, string(result))
		}
	})

	t.Run("writes to output", func(t *testing.T) {
		_, output := readThroughTeeReader(input)

		if output.String() != input {
			t.Errorf("Expected output to contain '%s', got '%s'", input, output.String())
		}
	})
}

// Test helpers
func readThroughTeeReader(input string) ([]byte, *bytes.Buffer) {
	reader := strings.NewReader(input)
	output := &bytes.Buffer{}
	teeReader := NewTeeReader(reader, output)
	result, _ := io.ReadAll(teeReader)
	return result, output
}
