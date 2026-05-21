import { useState, useMemo } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import { jsPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import * as XLSX from 'xlsx';
import {
  ArrowLeft, Lock, Search, Calendar, Download, FileSpreadsheet,
  Trash2, Eye, LogOut, Users, CalendarCheck,
} from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';
import {
  getRegistrations, deleteRegistration, verifyStaffPassword,
} from '../utils/storage';
import type { GuestRegistration } from '../types';

export default function StaffPage() {
  const { t } = useTranslation();
  const navigate = useNavigate();

  const [authenticated, setAuthenticated] = useState(false);
  const [password, setPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const [searchName, setSearchName] = useState('');
  const [searchDate, setSearchDate] = useState('');
  const [viewDetail, setViewDetail] = useState<GuestRegistration | null>(null);
  const [viewImage, setViewImage] = useState<{ src: string; title: string } | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  const handleLogin = (e: React.FormEvent) => {
    e.preventDefault();
    if (verifyStaffPassword(password)) {
      setAuthenticated(true);
      setPasswordError('');
    } else {
      setPasswordError(t('staffWrongPassword'));
    }
  };

  // eslint-disable-next-line react-hooks/exhaustive-deps -- refreshKey triggers re-read from localStorage
  const registrations = useMemo(() => getRegistrations(), [refreshKey]);

  const filtered = useMemo(() => {
    return registrations.filter((r) => {
      const nameMatch = !searchName ||
        `${r.firstName} ${r.lastName}`.toLowerCase().includes(searchName.toLowerCase()) ||
        `${r.lastName} ${r.firstName}`.toLowerCase().includes(searchName.toLowerCase());
      const dateMatch = !searchDate || r.arrivalDate === searchDate;
      return nameMatch && dateMatch;
    });
  }, [registrations, searchName, searchDate]);

  const todayArrivals = useMemo(() => {
    const today = new Date().toISOString().split('T')[0];
    return registrations.filter((r) => r.arrivalDate === today).length;
  }, [registrations]);

  const handleDelete = (id: string) => {
    if (window.confirm(t('staffConfirmDelete'))) {
      deleteRegistration(id);
      setViewDetail(null);
      setRefreshKey((k) => k + 1);
    }
  };

  const exportPdf = (reg: GuestRegistration) => {
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

    if (reg.signature) {
      try {
        const finalY = (doc as jsPDF & { lastAutoTable: { finalY: number } }).lastAutoTable.finalY || 200;
        doc.text('Signature:', 14, finalY + 10);
        doc.addImage(reg.signature, 'PNG', 14, finalY + 14, 60, 30);
      } catch {
        // Signature image failed
      }
    }

    doc.save(`fiche-${reg.lastName}-${reg.firstName}.pdf`);
  };

  const exportAllExcel = () => {
    const data = filtered.map((r) => ({
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

  if (!authenticated) {
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
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder={t('staffPassword')}
              className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg mb-3 focus:outline-none focus:ring-2 focus:ring-brown-600"
            />
            {passwordError && (
              <p className="text-red-600 text-sm mb-3">{passwordError}</p>
            )}
            <button
              type="submit"
              className="w-full py-3 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors"
            >
              {t('staffLogin')}
            </button>
          </form>
        </div>
      </div>
    );
  }

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
              onClick={() => setAuthenticated(false)}
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

        {filtered.length === 0 ? (
          <div className="text-center py-12 text-brown-600">
            <p>{t('staffNoRecords')}</p>
          </div>
        ) : (
          <div className="space-y-3">
            {filtered.map((r) => (
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
