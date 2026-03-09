import { loadConfig } from "@/lib/config";
import logger from "@/lib/logger";

export async function sendWebhook(event: string, data: Record<string, unknown>): Promise<void> {
  const config = loadConfig();
  const webhookUrl = config.notifications?.webhookUrl;

  if (!webhookUrl) return;

  try {
    new URL(webhookUrl);
  } catch {
    logger.warn({ webhookUrl }, "Invalid webhook URL configured");
    return;
  }

  const payload = {
    event,
    timestamp: new Date().toISOString(),
    data,
  };

  try {
    const res = await fetch(webhookUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
      signal: AbortSignal.timeout(10000),
    });

    logger.info({ event, webhookUrl, status: res.status }, "Webhook sent");
  } catch (err) {
    const error = err instanceof Error ? err.message : String(err);
    logger.error({ event, webhookUrl, error }, "Webhook delivery failed");
  }
}
