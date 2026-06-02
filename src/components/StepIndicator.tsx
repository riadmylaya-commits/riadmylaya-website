import { Check } from 'lucide-react';

interface StepIndicatorProps {
  steps: string[];
  currentStep: number;
}

export default function StepIndicator({ steps, currentStep }: StepIndicatorProps) {
  return (
    <div className="flex items-center justify-between mb-8">
      {steps.map((label, idx) => {
        const done = idx < currentStep;
        const active = idx === currentStep;
        return (
          <div key={idx} className="flex-1 flex items-center">
            <div className="flex flex-col items-center flex-1">
              <div
                className={`w-9 h-9 rounded-full flex items-center justify-center text-sm font-medium transition-colors ${
                  done
                    ? 'bg-green-600 text-white'
                    : active
                      ? 'bg-brown-700 text-beige-50'
                      : 'bg-beige-200 text-brown-600'
                }`}
              >
                {done ? <Check className="w-4 h-4" /> : idx + 1}
              </div>
              <span
                className={`mt-1.5 text-xs text-center leading-tight ${
                  active ? 'text-brown-900 font-medium' : 'text-brown-600'
                }`}
              >
                {label}
              </span>
            </div>
            {idx < steps.length - 1 && (
              <div
                className={`h-0.5 flex-1 mx-1 mt-[-1rem] ${
                  idx < currentStep ? 'bg-green-600' : 'bg-beige-300'
                }`}
              />
            )}
          </div>
        );
      })}
    </div>
  );
}
