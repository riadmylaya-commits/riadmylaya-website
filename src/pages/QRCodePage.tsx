import { useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { QRCodeSVG } from 'qrcode.react';
import { ArrowLeft, Link as LinkIcon, Download, Printer } from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';

const REGISTER_URL = typeof window !== 'undefined'
  ? `${window.location.origin}/register`
  : 'https://riad-guest-checkin.lovable.app/register';

export default function QRCodePage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const posterRef = useRef<HTMLDivElement>(null);

  const copyLink = async () => {
    await navigator.clipboard.writeText(REGISTER_URL);
  };

  const downloadPng = () => {
    const svg = posterRef.current?.querySelector('svg');
    if (!svg) return;
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const data = new XMLSerializer().serializeToString(svg);
    const img = new Image();
    img.onload = () => {
      canvas.width = img.width * 2;
      canvas.height = img.height * 2;
      ctx.scale(2, 2);
      ctx.drawImage(img, 0, 0);
      const link = document.createElement('a');
      link.download = 'riad-mylaya-qr.png';
      link.href = canvas.toDataURL('image/png');
      link.click();
    };
    img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(data)));
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="min-h-screen bg-beige-50">
      <div className="max-w-2xl mx-auto px-4 py-8">
        <div className="flex justify-between items-center mb-6 no-print">
          <button
            onClick={() => navigate('/')}
            className="flex items-center gap-1 text-brown-700 hover:text-brown-900"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('staffBack')}
          </button>
          <LanguageSelector />
        </div>

        <div className="no-print mb-6">
          <h1 className="text-2xl font-serif text-brown-900 mb-1">
            {t('qrPosterTitle')}
          </h1>
          <p className="text-sm text-brown-600">
            {t('qrCopyLink')}: {REGISTER_URL}
          </p>

          <div className="flex gap-3 mt-4 flex-wrap">
            <button
              onClick={copyLink}
              className="flex items-center gap-2 px-4 py-2 border border-beige-300 rounded-lg hover:bg-beige-200 transition-colors text-sm"
            >
              <LinkIcon className="w-4 h-4" />
              {t('qrCopyLink')}
            </button>
            <button
              onClick={downloadPng}
              className="flex items-center gap-2 px-4 py-2 border border-beige-300 rounded-lg hover:bg-beige-200 transition-colors text-sm"
            >
              <Download className="w-4 h-4" />
              {t('qrDownloadPng')}
            </button>
            <button
              onClick={handlePrint}
              className="flex items-center gap-2 px-4 py-2 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors text-sm"
            >
              <Printer className="w-4 h-4" />
              {t('qrPrint')}
            </button>
          </div>
        </div>

        <div
          ref={posterRef}
          className="bg-beige-50 border border-beige-200 rounded-2xl p-8 md:p-12 text-center"
        >
          <p className="text-xs tracking-[0.3em] text-brown-600 uppercase mb-6">
            RIAD MYLAYA — MARRAKECH
          </p>

          <h2 className="text-3xl md:text-4xl font-serif text-brown-900 mb-3">
            {t('qrTitle')}
          </h2>

          <p className="text-brown-700 mb-1 italic">{t('qrSubtitle')}</p>
          {t('qrSubtitleEn') && (
            <p className="text-brown-600 text-sm italic mb-8">{t('qrSubtitleEn')}</p>
          )}
          {!t('qrSubtitleEn') && <div className="mb-8" />}

          <div className="inline-block p-6 bg-white rounded-2xl shadow-sm mb-8">
            <QRCodeSVG
              value={REGISTER_URL}
              size={220}
              bgColor="#ffffff"
              fgColor="#3b1a10"
              level="H"
            />
          </div>

          <div className="space-y-4 text-left max-w-sm mx-auto mb-8">
            {[t('qrStep1'), t('qrStep2'), t('qrStep3')].map((step, i) => (
              <div key={i} className="flex items-center gap-4">
                <span className="flex-shrink-0 w-8 h-8 rounded-full bg-brown-700 text-beige-50 flex items-center justify-center text-sm font-medium">
                  {i + 1}
                </span>
                <span className="text-brown-800">{step}</span>
              </div>
            ))}
          </div>

          <div className="border-t border-beige-200 pt-6">
            <p className="text-sm text-brown-600 mb-1">{t('qrThankYou')}</p>
            <p className="text-xs text-brown-400">{REGISTER_URL}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
