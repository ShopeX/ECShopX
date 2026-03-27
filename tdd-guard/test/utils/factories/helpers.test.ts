import { describe, test, expect } from 'vitest'
import { pick, omit } from './helpers'

describe('Helper functions', () => {
  describe('pick', () => {
    const testObject = {
      a: 1,
      b: 'two',
      c: true,
      d: { nested: 'value' },
    }

    test('picks single property', () => {
      const result = pick(testObject, ['a'])

      expect(result).toEqual({ a: 1 })
    })

    test('picks multiple properties', () => {
      const result = pick(testObject, ['a', 'c'])

      expect(result).toEqual({ a: 1, c: true })
    })
  })

  describe('omit', () => {
    const testObject = {
      a: 1,
      b: 'two',
      c: true,
      d: { nested: 'value' },
    }

    test('omits single property', () => {
      const result = omit(testObject, ['a'])

      expect(result).toEqual({ b: 'two', c: true, d: { nested: 'value' } })
    })

    test('omits multiple properties', () => {
      const result = omit(testObject, ['a', 'c'])

      expect(result).toEqual({ b: 'two', d: { nested: 'value' } })
    })
  })
})
