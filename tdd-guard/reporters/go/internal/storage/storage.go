package storage

import (
	"encoding/json"
	"os"
	"path/filepath"

	"github.com/nizos/tdd-guard/reporters/go/internal/transformer"
)

var (
	// Path components for cross-platform compatibility
	TestResultsPath = []string{".claude", "tdd-guard", "data", "test.json"}
)

type Storage struct {
	basePath string
}

func NewStorage(projectRoot string) *Storage {
	return &Storage{basePath: projectRoot}
}

func (s *Storage) Save(results *transformer.TestResult) error {
	parts := append([]string{s.basePath}, TestResultsPath...)
	filePath := filepath.Join(parts...)

	// Ensure directory exists
	dir := filepath.Dir(filePath)
	os.MkdirAll(dir, 0755)

	// Marshal to JSON
	data, _ := json.Marshal(results)
	return os.WriteFile(filePath, data, 0644)
}
