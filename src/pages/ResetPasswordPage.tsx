import { useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { KeyRound, CheckCircle } from 'lucide-react';
import { api } from '../utils/api';

export default function ResetPasswordPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token') || '';

  const [newPw, setNewPw] = useState('');
  const [confirmPw, setConfirmPw] = useState('');
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (newPw !== confirmPw) {
      setError(t('passwordsDontMatch'));
      return;
    }
    if (newPw.length < 6) {
      setError(t('passwordTooShort'));
      return;
    }

    setLoading(true);
    try {
      await api.resetPassword(token, newPw);
      setSuccess(true);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error');
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="text-center max-w-sm">
          <CheckCircle className="w-16 h-16 text-green-600 mx-auto mb-6" />
          <h2 className="text-2xl font-serif text-brown-900 mb-4">
            {t('passwordResetSuccess')}
          </h2>
          <button
            onClick={() => navigate('/staff')}
            className="py-3 px-8 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors"
          >
            {t('staffLogin')}
          </button>
        </div>
      </div>
    );
  }

  if (!token) {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="text-center max-w-sm">
          <h2 className="text-xl font-serif text-brown-900 mb-4">
            {t('invalidResetLink')}
          </h2>
          <button
            onClick={() => navigate('/staff')}
            className="py-3 px-8 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors"
          >
            {t('staffBack')}
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <KeyRound className="w-12 h-12 text-brown-700 mx-auto mb-4" />
          <h1 className="text-2xl font-serif text-brown-900">{t('resetPassword')}</h1>
        </div>

        <form onSubmit={handleSubmit}>
          <input
            type="password"
            value={newPw}
            onChange={(e) => setNewPw(e.target.value)}
            placeholder={t('newPassword')}
            className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
          />
          <input
            type="password"
            value={confirmPw}
            onChange={(e) => setConfirmPw(e.target.value)}
            placeholder={t('confirmPassword')}
            className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
          />
          {error && <p className="text-red-600 text-sm mb-3">{error}</p>}
          <button
            type="submit"
            disabled={loading}
            className="w-full py-3 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors disabled:opacity-50"
          >
            {loading ? '...' : t('resetPassword')}
          </button>
        </form>
      </div>
    </div>
  );
}
