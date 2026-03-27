import { expect } from '@storybook/test'
import { NonExistent } from './non-existent-module' // This module doesn't exist

export default {
  title: 'Calculator',
  render: () => null, // No UI component, just testing logic
}

export const Primary = {
  name: 'should add numbers correctly',
  play: async () => {
    await expect(true).toBe(true)
  },
}
