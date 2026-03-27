package missingImportModule

import (
	"github.com/non-existent/module"
	"testing"
)

func TestCalculator(t *testing.T) {
	t.Run("TestShouldAddNumbersCorrectly", func(t *testing.T) {
		module.NonExistentFunction()
		result := 2 + 3
		expected := 5
		if result != expected {
			t.Errorf("Expected %d but got %d", expected, result)
		}
	})
}
