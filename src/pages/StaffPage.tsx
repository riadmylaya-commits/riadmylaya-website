import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';
import {
  ArrowLeft, Lock, Search, Calendar, Download, FileSpreadsheet,
  Trash2, Eye, LogOut, Users, CalendarCheck, KeyRound, Mail,
} from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';
import { api } from '../utils/api';
import type { GuestRegistration } from '../types';

type View = 'login' | 'forgot' | 'dashboard' | 'detail' | 'image' | 'changePassword';

export default function StaffPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();

  const [view, setView] = useState<View>(() =>
    localStorage.getItem('staff_token') ? 'dashboard' : 'login'
  );
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loginError, setLoginError] = useState('');
  const [loginLoading, setLoginLoading] = useState(false);

  const [forgotEmail, setForgotEmail] = useState('');
  const [forgotMsg, setForgotMsg] = useState('');
  const [forgotLoading, setForgotLoading] = useState(false);

  const [currentPw, setCurrentPw] = useState('');
  const [newPw, setNewPw] = useState('');
  const [confirmPw, setConfirmPw] = useState('');
  const [changePwMsg, setChangePwMsg] = useState('');
  const [changePwError, setChangePwError] = useState('');
  const [changePwLoading, setChangePwLoading] = useState(false);

  const [registrations, setRegistrations] = useState<GuestRegistration[]>([]);
  const [searchName, setSearchName] = useState('');
  const [searchDate, setSearchDate] = useState('');
  const [todayArrivals, setTodayArrivals] = useState(0);
  const [loading, setLoading] = useState(false);

  const [viewDetail, setViewDetail] = useState<GuestRegistration | null>(null);
  const [viewImage, setViewImage] = useState<{ src: string; title: string } | null>(null);

  const logout = useCallback(() => {
    localStorage.removeItem('staff_token');
    setView('login');
    setRegistrations([]);
    setUsername('');
    setPassword('');
  }, []);

  const loadRegistrations = useCallback(async () => {
    setLoading(true);
    try {
      const data = await api.getRegistrations(searchName, searchDate) as unknown as GuestRegistration[];
      setRegistrations(data);
      const stats = await api.getStats();
      setTodayArrivals(stats.todayArrivals);
    } catch (err) {
      if (err instanceof Error && err.message.includes('credentials')) {
        logout();
      }
    } finally {
      setLoading(false);
    }
  }, [searchName, searchDate, logout]);

  useEffect(() => {
    if (view !== 'dashboard') return;
    let cancelled = false;
    (async () => {
      setLoading(true);
      try {
        const data = await api.getRegistrations(searchName, searchDate) as unknown as GuestRegistration[];
        const stats = await api.getStats();
        if (!cancelled) {
          setRegistrations(data);
          setTodayArrivals(stats.todayArrivals);
        }
      } catch (err) {
        if (!cancelled && err instanceof Error && err.message.includes('credentials')) {
          logout();
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => { cancelled = true; };
  }, [view, searchName, searchDate, logout]);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoginError('');
    setLoginLoading(true);
    try {
      const res = await api.login(username, password);
      localStorage.setItem('staff_token', res.access_token);
      setView('dashboard');
    } catch (err) {
      setLoginError(err instanceof Error ? err.message : t('staffWrongPassword'));
    } finally {
      setLoginLoading(false);
    }
  };

  const handleForgotPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setForgotMsg('');
    setForgotLoading(true);
    try {
      await api.forgotPassword(forgotEmail);
      setForgotMsg(t('resetEmailSent'));
    } catch {
      setForgotMsg(t('resetEmailSent'));
    } finally {
      setForgotLoading(false);
    }
  };

  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setChangePwError('');
    setChangePwMsg('');
    if (newPw !== confirmPw) {
      setChangePwError(t('passwordsDontMatch'));
      return;
    }
    if (newPw.length < 6) {
      setChangePwError(t('passwordTooShort'));
      return;
    }
    setChangePwLoading(true);
    try {
      await api.changePassword(currentPw, newPw);
      setChangePwMsg(t('passwordChanged'));
      setCurrentPw('');
      setNewPw('');
      setConfirmPw('');
    } catch (err) {
      setChangePwError(err instanceof Error ? err.message : 'Error');
    } finally {
      setChangePwLoading(false);
    }
  };

  const handleDelete = async (id: string) => {
    if (window.confirm(t('staffConfirmDelete'))) {
      try {
        await api.deleteRegistration(id);
        setViewDetail(null);
        setView('dashboard');
        loadRegistrations();
      } catch {
        // ignore
      }
    }
  };

  const fetchImageAsBase64 = async (url: string): Promise<string | null> => {
    if (!url) return null;
    if (url.startsWith('data:')) return url;
    try {
      const res = await fetch(url);
      const blob = await res.blob();
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result as string);
        reader.readAsDataURL(blob);
      });
    } catch {
      return null;
    }
  };

  const exportPdf = async (reg: GuestRegistration) => {
    const doc = new jsPDF();
    doc.setFontSize(18);
    doc.text('Riad Mylaya — Fiche de Police', 14, 20);
    doc.setFontSize(10);

    const fields: [string, string][] = [
      ['Chambre / Room', reg.room],
      ['Nom / Surname', reg.lastName],
      ['Prénom / First name', reg.firstName],
      ['Date de naissance', reg.dateOfBirth],
      ['Lieu de naissance', reg.placeOfBirth],
      ['Nationalité', reg.nationality],
      ['Profession', reg.occupation],
      ['N° C.I.N', reg.cinNumber],
      ['N° entrée Maroc', reg.moroccoEntryNumber],
      ['Date d\'arrivée', reg.arrivalDate],
      ['Date de départ', reg.departureDate],
      ['Mineurs', String(reg.accompanyingChildren)],
      ['Provenance', reg.comingFrom],
      ['Destination', reg.goingTo],
      ['N° Passeport', reg.passportNumber],
      ['Date délivrance', reg.passportIssueDate],
      ['Lieu délivrance', reg.passportIssuePlace],
      ['Adresse', reg.permanentAddress],
      ['Date inscription', reg.registrationDate],
    ];

    autoTable(doc, {
      startY: 30,
      body: fields,
      theme: 'grid',
      styles: { fontSize: 9 },
      columnStyles: { 0: { fontStyle: 'bold', cellWidth: 50 } },
    });

    const finalY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY || 200;

    if (reg.passportPhoto) {
      try {
        const photoData = await fetchImageAsBase64(reg.passportPhoto);
        if (photoData) {
          doc.text('Photo du passeport:', 14, finalY + 10);
          doc.addImage(photoData, 'JPEG', 14, finalY + 14, 50, 35);
        }
      } catch {
        // Photo image failed
      }
    }

    if (reg.signature) {
      try {
        const sigData = await fetchImageAsBase64(reg.signature);
        if (sigData) {
          const sigY = reg.passportPhoto ? finalY + 55 : finalY + 10;
          doc.text('Signature:', 14, sigY);
          doc.addImage(sigData, 'PNG', 14, sigY + 4, 60, 30);
        }
      } catch {
        // Signature image failed
      }
    }

    doc.save(`fiche-${reg.lastName}-${reg.firstName}.pdf`);
  };

  const exportAllExcel = () => {
    const data = registrations.map((r) => ({
      Chambre: r.room,
      Nom: r.lastName,
      Prénom: r.firstName,
      'Date naissance': r.dateOfBirth,
      'Lieu naissance': r.placeOfBirth,
      Nationalité: r.nationality,
      Profession: r.occupation,
      CIN: r.cinNumber,
      'N° entrée': r.moroccoEntryNumber,
      Arrivée: r.arrivalDate,
      Départ: r.departureDate,
      Mineurs: r.accompanyingChildren,
      Provenance: r.comingFrom,
      Destination: r.goingTo,
      Passeport: r.passportNumber,
      'Date délivrance': r.passportIssueDate,
      'Lieu délivrance': r.passportIssuePlace,
      Adresse: r.permanentAddress,
      'Date inscription': r.registrationDate,
    }));

    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Fiches');
    XLSX.writeFile(wb, `fiches-riad-mylaya-${new Date().toISOString().split('T')[0]}.xlsx`);
  };

  // ---------- LOGIN ----------
  if (view === 'login') {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="w-full max-w-sm">
          <div className="flex justify-between items-center mb-8">
            <button
              onClick={() => navigate('/')}
              className="flex items-center gap-1 text-brown-700 hover:text-brown-900"
            >
              <ArrowLeft className="w-4 h-4" />
              {t('staffBack')}
            </button>
            <LanguageSelector />
          </div>

          <div className="text-center mb-8">
            <Lock className="w-12 h-12 text-brown-700 mx-auto mb-4" />
            <h1 className="text-2xl font-serif text-brown-900">{t('staffTitle')}</h1>
          </div>

          <form onSubmit={handleLogin}>
            <input
              type="text"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              placeholder={t('staffUsername')}
              className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder={t('staffPassword')}
              className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
            {loginError && (
              <p className="text-red-600 text-sm mb-3">{loginError}</p>
            )}
            <button
              type="submit"
              disabled={loginLoading}
              className="w-full py-3 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors disabled:opacity-50"
            >
              {loginLoading ? '...' : t('staffLogin')}
            </button>
          </form>

          <button
            onClick={() => { setView('forgot'); setForgotMsg(''); setForgotEmail(''); }}
            className="w-full mt-4 text-sm text-brown-600 hover:text-brown-800 text-center"
          >
            {t('forgotPassword')}
          </button>
        </div>
      </div>
    );
  }

  // ---------- FORGOT PASSWORD ----------
  if (view === 'forgot') {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="w-full max-w-sm">
          <button
            onClick={() => setView('login')}
            className="flex items-center gap-1 text-brown-700 hover:text-brown-900 mb-8"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('staffBack')}
          </button>

          <div className="text-center mb-8">
            <Mail className="w-12 h-12 text-brown-700 mx-auto mb-4" />
            <h1 className="text-2xl font-serif text-brown-900">{t('forgotPassword')}</h1>
            <p className="text-sm text-brown-600 mt-2">{t('forgotPasswordDesc')}</p>
          </div>

          <form onSubmit={handleForgotPassword}>
            <input
              type="email"
              value={forgotEmail}
              onChange={(e) => setForgotEmail(e.target.value)}
              placeholder="Email"
              className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
            {forgotMsg && (
              <p className="text-green-700 text-sm mb-3">{forgotMsg}</p>
            )}
            <button
              type="submit"
              disabled={forgotLoading}
              className="w-full py-3 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors disabled:opacity-50"
            >
              {forgotLoading ? '...' : t('sendResetLink')}
            </button>
          </form>
        </div>
      </div>
    );
  }

  // ---------- CHANGE PASSWORD ----------
  if (view === 'changePassword') {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="w-full max-w-sm">
          <button
            onClick={() => { setView('dashboard'); setChangePwMsg(''); setChangePwError(''); }}
            className="flex items-center gap-1 text-brown-700 hover:text-brown-900 mb-8"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('staffBack')}
          </button>

          <div className="text-center mb-8">
            <KeyRound className="w-12 h-12 text-brown-700 mx-auto mb-4" />
            <h1 className="text-2xl font-serif text-brown-900">{t('changePassword')}</h1>
          </div>

          <form onSubmit={handleChangePassword}>
            <input
              type="password"
              value={currentPw}
              onChange={(e) => setCurrentPw(e.target.value)}
              placeholder={t('currentPassword')}
              className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
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
            {changePwError && (
              <p className="text-red-600 text-sm mb-3">{changePwError}</p>
            )}
            {changePwMsg && (
              <p className="text-green-700 text-sm mb-3">{changePwMsg}</p>
            )}
            <button
              type="submit"
              disabled={changePwLoading}
              className="w-full py-3 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors disabled:opacity-50"
            >
              {changePwLoading ? '...' : t('changePassword')}
            </button>
          </form>
        </div>
      </div>
    );
  }

  // ---------- VIEW IMAGE ----------
  if (viewImage) {
    return (
      <div className="min-h-screen bg-beige-50 p-6">
        <div className="max-w-2xl mx-auto">
          <button
            onClick={() => setViewImage(null)}
            className="flex items-center gap-1 text-brown-700 hover:text-brown-900 mb-4"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('staffBack')}
          </button>
          <h2 className="text-xl font-serif text-brown-900 mb-4">{viewImage.title}</h2>
          <img
            src={viewImage.src}
            alt={viewImage.title}
            className="max-w-full rounded-lg border border-beige-300"
          />
        </div>
      </div>
    );
  }

  // ---------- VIEW DETAIL ----------
  if (viewDetail) {
    const r = viewDetail;
    const fields: [string, string][] = [
      [`${t('room')}`, r.room],
      [`${t('lastName')}`, r.lastName],
      [`${t('firstName')}`, r.firstName],
      [`${t('dateOfBirth')}`, r.dateOfBirth],
      [`${t('placeOfBirth')}`, r.placeOfBirth],
      [`${t('nationality')}`, r.nationality],
      [`${t('occupation')}`, r.occupation],
      [`${t('cinNumber')}`, r.cinNumber],
      [`${t('moroccoEntryNumber')}`, r.moroccoEntryNumber],
      [`${t('arrivalDate')}`, r.arrivalDate],
      [`${t('departureDate')}`, r.departureDate],
      [`${t('accompanyingChildren')}`, String(r.accompanyingChildren)],
      [`${t('comingFrom')}`, r.comingFrom],
      [`${t('goingTo')}`, r.goingTo],
      [`${t('passportNumber')}`, r.passportNumber],
      [`${t('passportIssueDate')}`, r.passportIssueDate],
      [`${t('passportIssuePlace')}`, r.passportIssuePlace],
      [`${t('permanentAddress')}`, r.permanentAddress],
      [`${t('dateField')}`, r.registrationDate],
    ];

    return (
      <div className="min-h-screen bg-beige-50 p-4 md:p-6">
        <div className="max-w-2xl mx-auto">
          <button
            onClick={() => setViewDetail(null)}
            className="flex items-center gap-1 text-brown-700 hover:text-brown-900 mb-4"
          >
            <ArrowLeft className="w-4 h-4" />
            {t('staffBack')}
          </button>

          <div className="bg-white/60 border border-beige-200 rounded-2xl p-6">
            <h2 className="text-xl font-serif text-brown-900 mb-4">
              {r.firstName} {r.lastName}
            </h2>

            <div className="space-y-2">
              {fields.map(([label, value]) => (
                <div key={label} className="flex flex-col sm:flex-row sm:gap-3 py-1 border-b border-beige-100">
                  <span className="font-medium text-brown-800 sm:w-48 flex-shrink-0">{label}</span>
                  <span className="text-brown-600">{value}</span>
                </div>
              ))}
            </div>

            <div className="flex flex-wrap gap-3 mt-6">
              {r.passportPhoto && (
                <button
                  onClick={() => setViewImage({ src: r.passportPhoto, title: t('staffViewPassport') })}
                  className="flex items-center gap-2 px-4 py-2 border border-beige-300 rounded-lg hover:bg-beige-200 text-sm"
                >
                  <Eye className="w-4 h-4" />
                  {t('staffViewPassport')}
                </button>
              )}
              {r.signature && (
                <button
                  onClick={() => setViewImage({ src: r.signature, title: t('staffViewSignature') })}
                  className="flex items-center gap-2 px-4 py-2 border border-beige-300 rounded-lg hover:bg-beige-200 text-sm"
                >
                  <Eye className="w-4 h-4" />
                  {t('staffViewSignature')}
                </button>
              )}
              <button
                onClick={() => exportPdf(r)}
                className="flex items-center gap-2 px-4 py-2 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 text-sm"
              >
                <Download className="w-4 h-4" />
                {t('staffExportPdf')}
              </button>
              <button
                onClick={() => handleDelete(r.id)}
                className="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm"
              >
                <Trash2 className="w-4 h-4" />
                {t('staffDelete')}
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // ---------- DASHBOARD ----------
  return (
    <div className="min-h-screen bg-beige-50">
      <div className="max-w-4xl mx-auto px-4 py-6">
        <div className="flex justify-between items-center mb-6">
          <div className="flex items-center gap-4">
            <button
              onClick={() => navigate('/')}
              className="flex items-center gap-1 text-brown-700 hover:text-brown-900"
            >
              <ArrowLeft className="w-4 h-4" />
            </button>
            <h1 className="text-2xl font-serif text-brown-900">{t('staffTitle')}</h1>
          </div>
          <div className="flex items-center gap-3">
            <LanguageSelector />
            <button
              onClick={() => setView('changePassword')}
              className="flex items-center gap-1 text-brown-600 hover:text-brown-800 text-sm"
              title={t('changePassword')}
            >
              <KeyRound className="w-4 h-4" />
            </button>
            <button
              onClick={logout}
              className="flex items-center gap-1 text-brown-600 hover:text-brown-800 text-sm"
            >
              <LogOut className="w-4 h-4" />
              {t('staffLogout')}
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
          <div className="bg-white/60 border border-beige-200 rounded-xl p-4 flex items-center gap-3">
            <CalendarCheck className="w-8 h-8 text-brown-700" />
            <div>
              <p className="text-2xl font-bold text-brown-900">{todayArrivals}</p>
              <p className="text-sm text-brown-600">{t('staffTodayArrivals')}</p>
            </div>
          </div>
          <div className="bg-white/60 border border-beige-200 rounded-xl p-4 flex items-center gap-3">
            <Users className="w-8 h-8 text-brown-700" />
            <div>
              <p className="text-2xl font-bold text-brown-900">{registrations.length}</p>
              <p className="text-sm text-brown-600">{t('staffTotalRegistrations')}</p>
            </div>
          </div>
        </div>

        <div className="flex flex-col sm:flex-row gap-3 mb-6">
          <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-brown-400" />
            <input
              type="text"
              value={searchName}
              onChange={(e) => setSearchName(e.target.value)}
              placeholder={t('staffSearchName')}
              className="w-full pl-10 pr-4 py-2.5 bg-beige-100 border border-beige-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
          </div>
          <div className="relative">
            <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-brown-400" />
            <input
              type="date"
              value={searchDate}
              onChange={(e) => setSearchDate(e.target.value)}
              className="w-full pl-10 pr-4 py-2.5 bg-beige-100 border border-beige-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
          </div>
          <button
            onClick={exportAllExcel}
            className="flex items-center gap-2 px-4 py-2.5 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors text-sm whitespace-nowrap"
          >
            <FileSpreadsheet className="w-4 h-4" />
            {t('staffExportExcel')}
          </button>
        </div>

        {loading ? (
          <div className="text-center py-12 text-brown-600">
            <p>...</p>
          </div>
        ) : registrations.length === 0 ? (
          <div className="text-center py-12 text-brown-600">
            <p>{t('staffNoRecords')}</p>
          </div>
        ) : (
          <div className="space-y-3">
            {registrations.map((r) => (
              <div
                key={r.id}
                onClick={() => setViewDetail(r)}
                className="bg-white/60 border border-beige-200 rounded-xl p-4 cursor-pointer hover:bg-beige-100 transition-colors"
              >
                <div className="flex justify-between items-start">
                  <div>
                    <p className="font-medium text-brown-900">
                      {r.firstName} {r.lastName}
                    </p>
                    <p className="text-sm text-brown-600">
                      {t('room')}: {r.room} · {r.nationality}
                    </p>
                  </div>
                  <div className="text-right text-sm text-brown-600">
                    <p>{r.arrivalDate} → {r.departureDate}</p>
                    <p className="text-xs">N° {r.passportNumber}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
