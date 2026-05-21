import { useTranslation } from 'react-i18next';
import { Globe } from 'lucide-react';

export default function LanguageSelector() {
  const { i18n } = useTranslation();

  const languages = [
    { code: 'fr', label: 'FR' },
    { code: 'en', label: 'EN' },
    { code: 'es', label: 'ES' },
  ];

  return (
    <div className="flex items-center gap-2">
      <Globe className="w-4 h-4 text-brown-700" />
      {languages.map((lang) => (
        <button
          key={lang.code}
          onClick={() => i18n.changeLanguage(lang.code)}
          className={`px-2 py-1 text-sm rounded transition-colors ${
            i18n.language === lang.code
              ? 'bg-brown-700 text-beige-50'
              : 'text-brown-700 hover:bg-beige-200'
          }`}
        >
          {lang.label}
        </button>
      ))}
    </div>
  );
}
