const { nonExistentFunction } = require('./non-existent-module')

describe('Calculator', () => {
  test('should add numbers correctly', () => {
    nonExistentFunction()
    expect(2 + 3).toBe(5)
  })
})
