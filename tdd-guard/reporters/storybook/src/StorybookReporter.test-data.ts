import type { TestContext } from './types'

const DEFAULT_STORY_ID = 'button--primary'
const DEFAULT_STORY_TITLE = 'Button'
const DEFAULT_STORY_NAME = 'Primary'

export function createStoryContext(
  overrides?: Partial<TestContext>
): TestContext {
  return {
    id: DEFAULT_STORY_ID,
    title: DEFAULT_STORY_TITLE,
    name: DEFAULT_STORY_NAME,
    ...overrides,
  }
}

// Convenience aliases for createStoryContext
export const passedStoryContext = createStoryContext
export const failedStoryContext = createStoryContext
export const skippedStoryContext = createStoryContext
export const renderErrorContext = createStoryContext
