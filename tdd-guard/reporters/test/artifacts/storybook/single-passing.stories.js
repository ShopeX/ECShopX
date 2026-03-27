import { expect } from '@storybook/test'
import { Calculator } from './Calculator'

export default {
  title: 'Calculator',
  render: () => null, // No UI component, just testing logic
}

export const Primary = {
  name: 'should add numbers correctly',
  play: async () => {
    const result = Calculator.add(2, 3)
    await expect(result).toBe(5)
  },
}
