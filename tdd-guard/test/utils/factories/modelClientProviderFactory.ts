import { ModelClientProvider } from '../../../src/providers/ModelClientProvider'
import { IModelClient } from '../../../src/contracts/types/ModelClient'
import { Config } from '../../../src/config/Config'

export function modelClientProvider(): ModelClientProvider {
  return new MockModelClientProvider()
}

class MockModelClientProvider extends ModelClientProvider {
  getModelClient(config?: Config): IModelClient {
    const actualConfig = config ?? new Config()

    return {
      ask: async () =>
        JSON.stringify({
          decision: undefined,
          reason: `Using mock model client with modelType: ${actualConfig.modelType}`,
        }),
    }
  }
}
