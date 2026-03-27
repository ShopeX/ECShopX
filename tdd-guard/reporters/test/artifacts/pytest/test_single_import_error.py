from non_existent_module import non_existent_function

class TestCalculator:
    def test_should_add_numbers_correctly(self):
        non_existent_module()
        assert 2 + 3 == 5