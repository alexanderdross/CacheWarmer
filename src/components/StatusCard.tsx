"use client";

interface StatusCardProps {
  label: string;
  value: string | number;
  color?: string;
}

export default function StatusCard({ label, value, color = "text-white" }: StatusCardProps) {
  return (
    <div className="bg-gray-900 border border-gray-800 rounded-lg p-4">
      <p className="text-sm text-gray-400 mb-1">{label}</p>
      <p className={`text-2xl font-bold ${color}`}>{value}</p>
    </div>
  );
}
