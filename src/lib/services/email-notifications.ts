import { loadConfig } from "@/lib/config";
import logger from "@/lib/logger";

export async function sendJobCompletedEmail(jobData: {
  jobId: string;
  status: string;
  sitemapUrl: string;
  totalUrls: number;
  processedUrls: number;
  durationMs?: number;
}): Promise<void> {
  const config = loadConfig();
  const emailConfig = config.notifications;

  if (!emailConfig?.emailEnabled || !emailConfig?.emailTo) {
    return;
  }

  const subject = `CacheWarmer: Job ${jobData.status} - ${jobData.sitemapUrl}`;
  const body = [
    `CacheWarmer Job ${jobData.status === "completed" ? "abgeschlossen" : "fehlgeschlagen"}`,
    "",
    `Job ID: ${jobData.jobId}`,
    `Sitemap: ${jobData.sitemapUrl}`,
    `Status: ${jobData.status}`,
    `URLs: ${jobData.processedUrls} / ${jobData.totalUrls}`,
    jobData.durationMs ? `Dauer: ${Math.round(jobData.durationMs / 1000)}s` : "",
    "",
    `-- CacheWarmer`,
  ].join("\n");

  if (emailConfig.smtpHost) {
    try {
      // eslint-disable-next-line @typescript-eslint/no-require-imports
      const nodemailer = require("nodemailer") as { createTransport: (opts: Record<string, unknown>) => { sendMail: (opts: Record<string, string>) => Promise<void> } };
      const transporter = nodemailer.createTransport({
        host: emailConfig.smtpHost,
        port: emailConfig.smtpPort || 587,
        secure: (emailConfig.smtpPort || 587) === 465,
        auth: emailConfig.smtpUser
          ? { user: emailConfig.smtpUser, pass: emailConfig.smtpPass || "" }
          : undefined,
      });

      await transporter.sendMail({
        from: emailConfig.emailFrom || "cachewarmer@localhost",
        to: emailConfig.emailTo,
        subject,
        text: body,
      });

      logger.info({ to: emailConfig.emailTo, jobId: jobData.jobId }, "Email notification sent");
    } catch (err) {
      const error = err instanceof Error ? err.message : String(err);
      logger.error({ error, to: emailConfig.emailTo }, "Failed to send email notification");
    }
  } else {
    logger.info(
      { to: emailConfig.emailTo, subject, jobId: jobData.jobId },
      "Email notification (SMTP not configured, logging instead)"
    );
  }
}
