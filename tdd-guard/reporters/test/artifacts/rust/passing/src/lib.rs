pub fn add(left: u64, right: u64) -> u64 {
    left + right
}

#[cfg(test)]
mod calculator_tests {
    use super::*;

    #[test]
    fn should_add_numbers_correctly() {
        let result = add(2, 3);
        assert_eq!(result, 5);
    }
}