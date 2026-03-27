export interface IModelClient {
  ask(prompt: string): Promise<string>
}
