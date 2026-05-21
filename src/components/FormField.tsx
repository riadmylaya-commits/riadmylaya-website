import { useTranslation } from 'react-i18next';

interface FormFieldProps {
  label: string;
  labelEn?: string;
  error?: string;
  children: React.ReactNode;
}

export default function FormField({ label, labelEn, error, children }: FormFieldProps) {
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
      {children}
      {error && (
        <p className="text-red-600 text-sm mt-1">{error}</p>
      )}
    </div>
  );
}
