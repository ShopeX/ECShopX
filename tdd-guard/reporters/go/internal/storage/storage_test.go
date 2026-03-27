package storage

import (
	"os"
	"path/filepath"
	"testing"
)

func TestStorage(t *testing.T) {
	t.Run("NewStorage", func(t *testing.T) {
		t.Run("creates storage with empty path", func(t *testing.T) {
			storage := NewStorage("")
			if storage == nil {
				t.Fatal("Expected storage to be created")
			}
		})

		t.Run("creates storage with project root", func(t *testing.T) {
			storage := NewStorage("/some/path")
			if storage == nil {
				t.Fatal("Expected storage to be created with root")
			}
		})
	})

	t.Run("Save", func(t *testing.T) {
		// Setup: Create temp directory and change to it for all tests
		oldWd, _ := os.Getwd()
		tempDir := t.TempDir()
		os.Chdir(tempDir)
		t.Cleanup(func() {
			os.Chdir(oldWd)
		})

		t.Run("returns no error", func(t *testing.T) {
			storage := NewStorage("")
			err := storage.Save(nil)
			if err != nil {
				t.Fatalf("Save failed: %v", err)
			}
		})

		t.Run("without project root", func(t *testing.T) {
			storage := NewStorage("")

			t.Run("creates directory in current directory", func(t *testing.T) {
				storage.Save(nil)

				// Extract directory path from TestResultsPath
				dirParts := TestResultsPath[:len(TestResultsPath)-1]
				expectedDir := filepath.Join(append([]string{tempDir}, dirParts...)...)

				if _, err := os.Stat(expectedDir); os.IsNotExist(err) {
					t.Fatal("Expected directory to be created")
				}
			})

			t.Run("creates file in current directory", func(t *testing.T) {
				storage.Save(nil)

				// Build expected file path
				parts := append([]string{tempDir}, TestResultsPath...)
				expectedPath := filepath.Join(parts...)

				if _, err := os.Stat(expectedPath); os.IsNotExist(err) {
					t.Fatal("Expected file to be created")
				}
			})
		})

		t.Run("with project root", func(t *testing.T) {
			projectDir := filepath.Join(tempDir, "project")
			storage := NewStorage(projectDir)

			t.Run("creates directory in project root", func(t *testing.T) {
				storage.Save(nil)

				// Extract directory path from TestResultsPath
				dirParts := TestResultsPath[:len(TestResultsPath)-1]
				expectedDir := filepath.Join(append([]string{projectDir}, dirParts...)...)

				if _, err := os.Stat(expectedDir); os.IsNotExist(err) {
					t.Fatal("Expected directory to be created in project root")
				}
			})

			t.Run("creates file in project root", func(t *testing.T) {
				storage.Save(nil)

				// Build expected file path
				parts := append([]string{projectDir}, TestResultsPath...)
				expectedPath := filepath.Join(parts...)

				if _, err := os.Stat(expectedPath); os.IsNotExist(err) {
					t.Fatal("Expected file to be created in project root")
				}
			})
		})

		t.Run("writes data to file", func(t *testing.T) {
			storage := NewStorage("")
			storage.Save(nil)

			parts := append([]string{tempDir}, TestResultsPath...)
			filePath := filepath.Join(parts...)
			content, _ := os.ReadFile(filePath)

			if len(content) == 0 {
				t.Fatal("Expected file to contain data")
			}
		})
	})
}
