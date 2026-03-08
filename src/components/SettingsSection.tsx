"use client";

import { type ReactNode } from "react";

interface SettingsSectionProps {
  title: string;
  description?: string;
  children: ReactNode;
  enabled?: boolean;
  onToggle?: (enabled: boolean) => void;
}

export default function SettingsSection({
  title,
  description,
  children,
  enabled,
  onToggle,
}: SettingsSectionProps) {
  return (
    <div className="bg-gray-900 border border-gray-800 rounded-lg p-6">
      <div className="flex items-center justify-between mb-4">
        <div>
          <h3 className="text-md font-semibold">{title}</h3>
          {description && <p className="text-sm text-gray-400 mt-1">{description}</p>}
        </div>
        {onToggle !== undefined && (
          <button
            onClick={() => onToggle(!enabled)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              enabled ? "bg-orange-600" : "bg-gray-700"
            }`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                enabled ? "translate-x-6" : "translate-x-1"
              }`}
            />
          </button>
        )}
      </div>
      <div className={onToggle !== undefined && !enabled ? "opacity-40 pointer-events-none" : ""}>
        {children}
      </div>
    </div>
  );
}
