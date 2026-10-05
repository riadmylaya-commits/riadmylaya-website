import { useTranslation } from 'react-i18next';

interface FormFieldProps {
  label: string;
  labelEn?: string;
  helpText?: string;
  error?: string;
  children: React.ReactNode;
}

export default function FormField({ label, labelEn, helpText, error, children }: FormFieldProps) {
  const { i18n } = useTranslation();
  const showBilingual = i18n.language === 'fr' && labelEn;

  return (
    <div className="mb-5">
      <label className="block mb-1.5">
        <span className="font-medium text-brown-900">{label}</span>
        {showBilingual && (
          <span className="text-brown-600 text-sm ml-1">/ {labelEn}</span>
        )}
      </label>
      {helpText && (
        <p className="text-brown-500 text-sm mb-1.5 italic">{helpText}</p>
      )}
      {children}
      {error && (
        <p className="text-red-600 text-sm mt-1">{error}</p>
      )}
    </div>
  );
}
