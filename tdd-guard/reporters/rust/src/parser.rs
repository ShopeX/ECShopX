// src/parser.rs - Simplified parser for test events

use serde::{Deserialize, Serialize};
use serde_json::Value;

/// Test event from cargo test/nextest JSON output
#[derive(Debug, Clone, Deserialize, Serialize)]
#[serde(tag = "type", rename_all = "lowercase")]
pub enum TestEvent {
    Suite {
        event: String,
        #[serde(default)]
        passed: u32,
        #[serde(default)]
        failed: u32,
        #[serde(default)]
        ignored: u32,
    },
    Test {
        name: String,
        event: String,
        #[serde(default)]
        stdout: Option<String>,
        #[serde(default)]
        stderr: Option<String>,
    },
    #[serde(untagged)]
    Other(Value),
}

impl TestEvent {
    /// Get error message from stdout/stderr
    pub fn error_message(&self) -> Option<String> {
        match self {
            TestEvent::Test { stdout, stderr, .. } => {
                let mut msg = String::new();

                if let Some(out) = stdout {
                    msg.push_str(out.trim());
                }

                if let Some(err) = stderr {
                    if !msg.is_empty() {
                        msg.push('\n');
                    }
                    msg.push_str(err.trim());
                }

                if msg.is_empty() {
                    None
                } else {
                    Some(msg)
                }
            }
            _ => None,
        }
    }

    /// Extract module name from test path
    pub fn module_name(&self) -> String {
        match self {
            TestEvent::Test { name, .. } => extract_module(name),
            _ => "tests".to_string(),
        }
    }

    /// Extract simple test name
    pub fn simple_test_name(&self) -> String {
        match self {
            TestEvent::Test { name, .. } => extract_test_name(name),
            _ => String::new(),
        }
    }
}

/// Extract module from test path
fn extract_module(path: &str) -> String {
    // Handle doc tests: "src/lib.rs - function (line X)"
    if path.contains(" - ") && path.contains(" (line ") {
        return "doctests".to_string();
    }

    // Handle integration tests: "tests/integration.rs: test_name"
    if path.starts_with("tests/") && path.contains(':') {
        if let Some(file) = path.split(':').next() {
            return file.trim_end_matches(".rs").replace('/', "::");
        }
    }

    // Handle nextest format with $
    if let Some(pos) = path.find('$') {
        let module = &path[..pos];
        // Remove duplicate crate name if present
        if let Some(colon_pos) = module.rfind("::") {
            let prefix = &module[..colon_pos];
            let suffix = &module[colon_pos + 2..];
            if prefix == suffix {
                return prefix.to_string();
            }
        }
        return module.to_string();
    }

    // Standard format: extract first component
    if let Some(pos) = path.find("::") {
        return path[..pos].to_string();
    }

    "tests".to_string()
}

/// Extract test name from path
fn extract_test_name(path: &str) -> String {
    // Handle doc tests
    if path.contains(" - ") && path.contains(" (line ") {
        if let Some(start) = path.find(" - ") {
            let after = &path[start + 3..];
            if let Some(end) = after.find(" (line ") {
                return after[..end].to_string();
            }
        }
    }

    // Handle integration tests
    if path.starts_with("tests/") && path.contains(": ") {
        if let Some(pos) = path.find(": ") {
            return path[pos + 2..].to_string();
        }
    }

    // Handle nextest format
    if let Some(pos) = path.find('$') {
        return path[pos + 1..].to_string();
    }

    // Standard format: last component
    if let Some(pos) = path.rfind("::") {
        return path[pos + 2..].to_string();
    }

    path.to_string()
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_extract_module() {
        assert_eq!(extract_module("src/lib.rs - add (line 7)"), "doctests");
        assert_eq!(
            extract_module("tests/integration: test_basic"),
            "tests::integration"
        );
        assert_eq!(
            extract_module("my_crate::my_crate$tests::test_fn"),
            "my_crate"
        );
        assert_eq!(extract_module("my_crate::tests::test_fn"), "my_crate");
    }

    #[test]
    fn test_extract_test_name() {
        assert_eq!(
            extract_test_name("src/lib.rs - module::add (line 7)"),
            "module::add"
        );
        assert_eq!(
            extract_test_name("tests/integration: test_basic"),
            "test_basic"
        );
        assert_eq!(extract_test_name("crate$tests::test_fn"), "tests::test_fn");
        assert_eq!(extract_test_name("crate::tests::test_fn"), "test_fn");
    }

    #[test]
    fn test_error_message() {
        let event = TestEvent::Test {
            name: "test".to_string(),
            event: "failed".to_string(),
            stdout: Some("assertion failed".to_string()),
            stderr: Some("error details".to_string()),
        };

        let msg = event.error_message().unwrap();
        assert!(msg.contains("assertion failed"));
        assert!(msg.contains("error details"));
    }

    #[test]
    fn test_doctest_paths_module_and_name() {
        let e = TestEvent::Test {
            name: "src/lib.rs - my_mod::add (line 7)".to_string(),
            event: "ok".to_string(),
            stdout: None,
            stderr: None,
        };
        assert_eq!(e.module_name(), "doctests");
        assert_eq!(e.simple_test_name(), "my_mod::add");
    }

    #[test]
    fn test_integration_test_paths() {
        let e = TestEvent::Test {
            name: "tests/integration.rs: test_basic".to_string(),
            event: "ok".to_string(),
            stdout: None,
            stderr: None,
        };
        assert_eq!(e.module_name(), "tests::integration");
        assert_eq!(e.simple_test_name(), "test_basic");
    }

    #[test]
    fn test_nextest_dollar_format_and_duplicate_crate() {
        let e = TestEvent::Test {
            name: "my_crate::my_crate$tests::test_fn".to_string(),
            event: "ok".to_string(),
            stdout: None,
            stderr: None,
        };
        assert_eq!(e.module_name(), "my_crate");
        assert_eq!(e.simple_test_name(), "tests::test_fn");
    }
}
