import { NextRequest, NextResponse } from "next/server";
import { authenticateRequest } from "@/lib/auth";
import { getConfig } from "@/lib/config";
import { createClient } from "@supabase/supabase-js";

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  const authError = authenticateRequest(request);
  if (authError) return authError;

  const { id } = await params;
  const cfg = getConfig().schemaValidation;
  if (!cfg) {
    return NextResponse.json({ jobId: id, results: [] });
  }

  const url = process.env[cfg.supabase.urlEnv];
  const key = process.env[cfg.supabase.serviceRoleKeyEnv];
  if (!url || !key) {
    return NextResponse.json(
      { error: "Supabase env vars not configured" },
      { status: 500 }
    );
  }

  const supabase = createClient(url, key, {
    auth: { persistSession: false, autoRefreshToken: false },
  });

  const { data, error } = await supabase
    .from(cfg.supabase.table)
    .select("*")
    .eq("job_id", id)
    .order("created_at", { ascending: true });

  if (error) {
    return NextResponse.json({ error: error.message }, { status: 500 });
  }

  return NextResponse.json({ jobId: id, results: data ?? [] });
}
