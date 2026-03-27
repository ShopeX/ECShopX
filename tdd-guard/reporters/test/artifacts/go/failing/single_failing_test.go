package singleFailingTestModule

import "testing"

func TestCalculator(t *testing.T) {
	t.Run("TestShouldAddNumbersCorrectly", func(t *testing.T) {
		result := 2 + 3
		expected := 6
		if result != expected {
			t.Errorf("Expected %d but got %d", expected, result)
		}
	})
}
