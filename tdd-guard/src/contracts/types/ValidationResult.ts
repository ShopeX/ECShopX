export type ValidationResult = {
  decision: 'approve' | 'block' | undefined
  reason: string
  continue?: boolean
  stopReason?: string
}
