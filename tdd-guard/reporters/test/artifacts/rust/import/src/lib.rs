use non_existent_module::Calculator; // This will fail to compile

#[cfg(test)]
mod calculator_tests {
    use super::*;

    #[test]
    fn should_add_numbers_correctly() {
        let calc = Calculator::new();
        let result = calc.add(2, 3);
        assert_eq!(result, 5);
    }
}