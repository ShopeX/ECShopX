import { IModelClient } from '../contracts/types/ModelClient'
import { Config } from '../config/Config'
import { ClaudeCli } from '../validation/models/ClaudeCli'
import { CursorCli } from '../validation/models/CursorCli'
import { AnthropicApi } from '../validation/models/AnthropicApi'
import { ClaudeAgentSdk } from '../validation/models/ClaudeAgentSdk'

export class ModelClientProvider {
  getModelClient(config?: Config): IModelClient {
    const actualConfig = config ?? new Config()

    switch (actualConfig.validationClient) {
      case 'sdk':
        return new ClaudeAgentSdk(actualConfig)
      case 'api':
        return new AnthropicApi(actualConfig)
      case 'cli':
        return new ClaudeCli(actualConfig)
      case 'cursor-cli':
        return new CursorCli(actualConfig)
      default:
        return new ClaudeCli(actualConfig)
    }
  }
}
