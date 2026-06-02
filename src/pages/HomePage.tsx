import { Link } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { Phone, Mail, HelpCircle, MapPin, Star } from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';

export default function HomePage() {
  const { t, i18n } = useTranslation();
  const isFr = i18n.language === 'fr';

  return (
    <div className="min-h-screen bg-beige-50 flex flex-col items-center">
      <div className="w-full max-w-lg mx-auto px-6 py-12 text-center">
        <div className="absolute top-4 right-4">
          <LanguageSelector />
        </div>

        <div className="flex items-center justify-center gap-1 text-brown-600 mb-4">
          <MapPin className="w-3.5 h-3.5" />
          <p className="text-xs tracking-[0.25em] uppercase">
            {t('address')}
          </p>
        </div>

        <h1 className="text-5xl md:text-6xl font-serif text-brown-900 mb-2">
          {t('riadName')}
        </h1>

        <div className="flex items-center justify-center gap-0.5 mb-4">
          {[...Array(4)].map((_, i) => (
            <Star key={i} className="w-4 h-4 text-brown-700 fill-brown-700" />
          ))}
        </div>

        <div className="w-16 h-0.5 bg-brown-700 mx-auto mb-8" />

        <h2 className="text-2xl md:text-3xl font-serif text-brown-900 mb-6">
          {t('pageTitle')}
        </h2>

        <div className="text-brown-700 text-base leading-relaxed mb-10 px-2">
          {isFr ? (
            <>
              <p className="mb-2">{t('welcomeText')}</p>
              <p>{t('welcomeTextEn')}</p>
            </>
          ) : (
            <p>{t('welcomeText')}</p>
          )}
        </div>

        <div className="space-y-4 mb-8">
          <Link
            to="/register"
            className="block w-full py-4 px-6 bg-brown-700 text-beige-50 text-lg rounded-lg hover:bg-brown-800 transition-colors shadow-sm"
          >
            {t('fillForm')}
          </Link>

          <Link
            to="/staff"
            className="block w-full py-4 px-6 border-2 border-brown-700 text-brown-700 text-lg rounded-lg hover:bg-beige-200 transition-colors"
          >
            {t('staffArea')}
          </Link>
        </div>

        <Link
          to="/qr"
          className="inline-flex items-center gap-2 text-brown-600 hover:text-brown-800 text-sm mb-10"
        >
          <HelpCircle className="w-4 h-4" />
          <span className="underline">
            {isFr ? 'Affiche QR Code' : t('qrPosterTitle')}
          </span>
        </Link>

        <div className="text-sm text-brown-600 flex items-center justify-center gap-1 flex-wrap">
          <Phone className="w-3.5 h-3.5" />
          <a href={`tel:${t('phone').replace(/\s/g, '')}`} className="hover:underline">
            {t('phone')}
          </a>
          <span className="mx-1">·</span>
          <Mail className="w-3.5 h-3.5" />
          <a href={`mailto:${t('email')}`} className="hover:underline">
            {t('email')}
          </a>
        </div>
      </div>
    </div>
  );
}
