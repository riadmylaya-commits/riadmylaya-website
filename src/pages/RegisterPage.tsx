import { useState, useRef, useCallback, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import SignatureCanvas from 'react-signature-canvas';
import { ArrowLeft, CheckCircle, ChevronRight, ChevronLeft, Calendar } from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';
import FormField from '../components/FormField';
import StepIndicator from '../components/StepIndicator';
import { api } from '../utils/api';

const STORAGE_KEY = 'riadmylaya_draft';
const today = () => new Date().toISOString().split('T')[0];

const ROOMS = [
  { value: 'suite-royale', labelKey: 'roomSuiteRoyale' },
  { value: 'chambre-jasmin', labelKey: 'roomChambreJasmin' },
  { value: 'chambre-rose', labelKey: 'roomChambreRose' },
  { value: 'chambre-ambre', labelKey: 'roomChambreAmbre' },
  { value: 'chambre-safran', labelKey: 'roomChambreSafran' },
];

const NATIONALITIES = [
  'Française', 'Marocaine', 'Américaine', 'Britannique', 'Allemande',
  'Espagnole', 'Italienne', 'Néerlandaise', 'Belge', 'Suisse',
  'Canadienne', 'Portugaise', 'Brésilienne', 'Japonaise', 'Chinoise',
  'Australienne', 'Autrichienne', 'Suédoise', 'Danoise', 'Norvégienne',
  'Polonaise', 'Tchèque', 'Russe', 'Turque', 'Indienne',
  'Saoudienne', 'Émiratie', 'Algérienne', 'Tunisienne', 'Sénégalaise',
];

type FormData = {
  room: string;
  lastName: string;
  firstName: string;
  dateOfBirth: string;
  placeOfBirth: string;
  nationality: string;
  occupation: string;
  cinNumber: string;
  moroccoEntryNumber: string;
  arrivalDate: string;
  departureDate: string;
  accompanyingChildren: number;
  comingFrom: string;
  goingTo: string;
  passportNumber: string;
  passportIssueDate: string;
  passportIssuePlace: string;
  permanentAddress: string;
  registrationDate: string;
};

const defaultForm: FormData = {
  room: '',
  lastName: '',
  firstName: '',
  dateOfBirth: '',
  placeOfBirth: '',
  nationality: '',
  occupation: '',
  cinNumber: '',
  moroccoEntryNumber: '',
  arrivalDate: '',
  departureDate: '',
  accompanyingChildren: 0,
  comingFrom: '',
  goingTo: '',
  passportNumber: '',
  passportIssueDate: '',
  passportIssuePlace: '',
  permanentAddress: '',
  registrationDate: today(),
};

function loadDraft(): FormData {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) return { ...defaultForm, ...JSON.parse(raw) };
  } catch { /* ignore */ }
  return { ...defaultForm };
}

function nightsBetween(arrival: string, departure: string): number {
  if (!arrival || !departure) return 0;
  const diff = new Date(departure).getTime() - new Date(arrival).getTime();
  return Math.max(0, Math.round(diff / 86400000));
}

export default function RegisterPage() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const sigCanvas = useRef<SignatureCanvas>(null);
  const isFr = i18n.language === 'fr';

  const [step, setStep] = useState(0);
  const [form, setForm] = useState<FormData>(loadDraft);
  const [passportPhoto, setPassportPhoto] = useState<string>('');
  const [passportFileName, setPassportFileName] = useState<string>('');
  const [signatureData, setSignatureData] = useState<string>('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [attemptedSubmit, setAttemptedSubmit] = useState(false);
  const [showSavedToast, setShowSavedToast] = useState(false);

  const stepLabels = [t('stepPersonal'), t('stepTravel'), t('stepDocuments')];

  // Auto-save to localStorage
  useEffect(() => {
    const timer = setTimeout(() => {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(form));
      setShowSavedToast(true);
      setTimeout(() => setShowSavedToast(false), 1500);
    }, 1000);
    return () => clearTimeout(timer);
  }, [form]);

  const updateField = (field: keyof FormData, value: string | number) => {
    setForm((prev) => ({ ...prev, [field]: value }));
    if (attemptedSubmit) {
      setErrors((prev) => {
        const next = { ...prev };
        delete next[field];
        return next;
      });
    }
  };

  const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setPassportFileName(file.name);
    const reader = new FileReader();
    reader.onloadend = () => {
      setPassportPhoto(reader.result as string);
      if (attemptedSubmit) {
        setErrors((prev) => {
          const next = { ...prev };
          delete next['passportPhoto'];
          return next;
        });
      }
    };
    reader.readAsDataURL(file);
  };

  const clearSignature = () => {
    sigCanvas.current?.clear();
    setSignatureData('');
  };

  const handleSignatureEnd = () => {
    if (sigCanvas.current) {
      const data = sigCanvas.current.toDataURL();
      setSignatureData(data);
      if (attemptedSubmit) {
        setErrors((prev) => {
          const next = { ...prev };
          delete next['signature'];
          return next;
        });
      }
    }
  };

  const validateStep = useCallback((s: number): Record<string, string> => {
    const errs: Record<string, string> = {};
    const req = t('requiredField');

    if (s === 0) {
      const fields: (keyof FormData)[] = ['room', 'lastName', 'firstName', 'dateOfBirth', 'placeOfBirth', 'nationality', 'occupation'];
      for (const f of fields) {
        if (!form[f] || form[f] === '') errs[f] = req;
      }
    }

    if (s === 1) {
      const fields: (keyof FormData)[] = ['cinNumber', 'moroccoEntryNumber', 'arrivalDate', 'departureDate', 'comingFrom', 'goingTo'];
      for (const f of fields) {
        if (!form[f] || form[f] === '') errs[f] = req;
      }
      if (form.arrivalDate && form.arrivalDate < today()) {
        errs['arrivalDate'] = t('dateErrorArrivalPast');
      }
      if (form.arrivalDate && form.departureDate && form.departureDate <= form.arrivalDate) {
        errs['departureDate'] = t('dateErrorDeparture');
      }
    }

    if (s === 2) {
      const fields: (keyof FormData)[] = ['passportNumber', 'passportIssueDate', 'passportIssuePlace', 'permanentAddress'];
      for (const f of fields) {
        if (!form[f] || form[f] === '') errs[f] = req;
      }
      if (!passportPhoto) errs['passportPhoto'] = t('requiredPhoto');
      if (!signatureData) errs['signature'] = t('requiredSignature');
    }

    return errs;
  }, [form, passportPhoto, signatureData, t]);

  const validateAll = useCallback((): Record<string, string> => {
    return { ...validateStep(0), ...validateStep(1), ...validateStep(2) };
  }, [validateStep]);

  const goNext = () => {
    const errs = validateStep(step);
    if (Object.keys(errs).length > 0) {
      setErrors(errs);
      setAttemptedSubmit(true);
      const el = document.querySelector('[data-error="true"]');
      el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    setErrors({});
    setStep((s) => Math.min(s + 1, 2));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const goPrev = () => {
    setErrors({});
    setStep((s) => Math.max(s - 1, 0));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setAttemptedSubmit(true);

    const validationErrors = validateAll();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      const firstStep = Object.keys(validationErrors).some(k =>
        ['room', 'lastName', 'firstName', 'dateOfBirth', 'placeOfBirth', 'nationality', 'occupation'].includes(k)
      ) ? 0 : Object.keys(validationErrors).some(k =>
        ['cinNumber', 'moroccoEntryNumber', 'arrivalDate', 'departureDate', 'comingFrom', 'goingTo'].includes(k)
      ) ? 1 : 2;
      setStep(firstStep);
      return;
    }

    setSubmitting(true);
    try {
      await api.submitRegistration({
        ...form,
        passportPhoto,
        signature: signatureData,
      });
      localStorage.removeItem(STORAGE_KEY);
      setSubmitted(true);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch {
      setErrors({ form: t('submitError') || 'Submission failed. Please try again.' });
    } finally {
      setSubmitting(false);
    }
  };

  const nights = nightsBetween(form.arrivalDate, form.departureDate);
  const roomLabel = ROOMS.find(r => r.value === form.room);

  if (submitted) {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="text-center max-w-md">
          <CheckCircle className="w-16 h-16 text-green-600 mx-auto mb-6" />
          <h2 className="text-2xl font-serif text-brown-900 mb-4">
            {t('successMessage')}
          </h2>
          {isFr && (
            <p className="text-brown-600 mb-4">{t('successMessageEn')}</p>
          )}

          <div className="bg-white/60 border border-beige-200 rounded-xl p-5 text-left mb-6">
            <h3 className="font-serif text-brown-900 text-lg mb-3">
              {t('yourStay')}
              {isFr && t('yourStayEn') && (
                <span className="text-brown-600 text-sm ml-1">/ {t('yourStayEn')}</span>
              )}
            </h3>
            <div className="space-y-1 text-sm text-brown-700">
              <p><span className="font-medium">{t('room')}:</span> {roomLabel ? t(roomLabel.labelKey) : form.room}</p>
              <p><span className="font-medium">{form.firstName} {form.lastName}</span></p>
              {form.arrivalDate && form.departureDate && (
                <p>
                  <Calendar className="w-3.5 h-3.5 inline mr-1" />
                  {form.arrivalDate} → {form.departureDate}
                  {nights > 0 && <span className="ml-1 text-brown-600">({nights} {t('nightsCount')})</span>}
                </p>
              )}
            </div>
          </div>

          <p className="text-brown-600 text-sm mb-6">{t('successDetail')}</p>
          {isFr && t('successDetailEn') && (
            <p className="text-brown-600 text-sm mb-6">{t('successDetailEn')}</p>
          )}

          <button
            onClick={() => navigate('/')}
            className="py-3 px-8 bg-brown-700 text-beige-50 rounded-lg hover:bg-brown-800 transition-colors"
          >
            {t('staffBack')}
          </button>
        </div>
      </div>
    );
  }

  const inputClass = (field: string) =>
    `w-full px-4 py-3 bg-beige-100 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600 ${
      errors[field] ? 'border-red-500' : 'border-beige-300'
    }`;

  const renderInput = (field: keyof FormData, labelKey: string, labelEnKey: string, type = 'text') => (
    <FormField
      label={t(labelKey)}
      labelEn={isFr ? t(labelEnKey) || undefined : undefined}
      error={errors[field]}
    >
      <div data-error={!!errors[field]}>
        <input
          type={type}
          value={form[field] as string}
          onChange={(e) => updateField(field, e.target.value)}
          className={inputClass(field)}
        />
      </div>
    </FormField>
  );

  const renderDateInput = (field: keyof FormData, labelKey: string, labelEnKey: string, min?: string) => (
    <FormField
      label={t(labelKey)}
      labelEn={isFr ? t(labelEnKey) || undefined : undefined}
      error={errors[field]}
    >
      <div data-error={!!errors[field]}>
        <input
          type="date"
          min={min}
          value={form[field] as string}
          onChange={(e) => updateField(field, e.target.value)}
          className={inputClass(field)}
        />
      </div>
    </FormField>
  );

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

        <div className="text-center mb-6">
          <p className="text-xs tracking-[0.25em] text-brown-600 uppercase mb-2">
            RIAD MYLAYA
          </p>
          <h1 className="text-3xl md:text-4xl font-serif text-brown-900 mb-1">
            {t('formTitle')}
          </h1>
          <p className="text-brown-600">{t('formSubtitle')}</p>
        </div>

        <StepIndicator steps={stepLabels} currentStep={step} />

        {/* Saved toast */}
        {showSavedToast && (
          <div className="fixed top-4 right-4 bg-green-600 text-white text-sm px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in">
            {t('savedDraft')}
          </div>
        )}

        <form onSubmit={handleSubmit} noValidate>
          <div className="bg-white/60 border border-beige-200 rounded-2xl p-6 md:p-8">

            {/* Step 0: Personal Info */}
            {step === 0 && (
              <>
                <FormField
                  label={t('room')}
                  labelEn={isFr ? t('roomEn') || undefined : undefined}
                  error={errors['room']}
                >
                  <div data-error={!!errors['room']}>
                    <select
                      value={form.room}
                      onChange={(e) => updateField('room', e.target.value)}
                      className={inputClass('room')}
                    >
                      <option value="">{t('selectRoom')}</option>
                      {ROOMS.map((r) => (
                        <option key={r.value} value={r.value}>{t(r.labelKey)}</option>
                      ))}
                    </select>
                  </div>
                </FormField>

                {renderInput('lastName', 'lastName', 'lastNameEn')}
                {renderInput('firstName', 'firstName', 'firstNameEn')}
                {renderDateInput('dateOfBirth', 'dateOfBirth', 'dateOfBirthEn')}
                {renderInput('placeOfBirth', 'placeOfBirth', 'placeOfBirthEn')}

                <FormField
                  label={t('nationality')}
                  labelEn={isFr ? t('nationalityEn') || undefined : undefined}
                  error={errors['nationality']}
                >
                  <div data-error={!!errors['nationality']}>
                    <input
                      list="nationalities"
                      value={form.nationality}
                      onChange={(e) => updateField('nationality', e.target.value)}
                      placeholder={t('selectNationality')}
                      className={inputClass('nationality')}
                    />
                    <datalist id="nationalities">
                      {NATIONALITIES.map((n) => (
                        <option key={n} value={n} />
                      ))}
                    </datalist>
                  </div>
                </FormField>

                {renderInput('occupation', 'occupation', 'occupationEn')}
              </>
            )}

            {/* Step 1: Travel/Stay */}
            {step === 1 && (
              <>
                {renderInput('cinNumber', 'cinNumber', 'cinNumberEn')}
                {renderInput('moroccoEntryNumber', 'moroccoEntryNumber', 'moroccoEntryNumberEn')}

                {renderDateInput('arrivalDate', 'arrivalDate', 'arrivalDateEn', today())}
                {renderDateInput('departureDate', 'departureDate', 'departureDateEn', form.arrivalDate || today())}

                {nights > 0 && (
                  <div className="text-sm text-brown-600 mb-4 -mt-3 pl-1">
                    <Calendar className="w-3.5 h-3.5 inline mr-1" />
                    {nights} {t('nightsCount')}
                  </div>
                )}

                <FormField
                  label={t('accompanyingChildren')}
                  labelEn={isFr ? t('accompanyingChildrenEn') || undefined : undefined}
                >
                  <input
                    type="number"
                    min="0"
                    value={form.accompanyingChildren}
                    onChange={(e) => updateField('accompanyingChildren', parseInt(e.target.value) || 0)}
                    className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600"
                  />
                </FormField>

                {renderInput('comingFrom', 'comingFrom', 'comingFromEn')}
                {renderInput('goingTo', 'goingTo', 'goingToEn')}
              </>
            )}

            {/* Step 2: Documents */}
            {step === 2 && (
              <>
                {renderInput('passportNumber', 'passportNumber', 'passportNumberEn')}
                {renderDateInput('passportIssueDate', 'passportIssueDate', 'passportIssueDateEn')}
                {renderInput('passportIssuePlace', 'passportIssuePlace', 'passportIssuePlaceEn')}

                <FormField
                  label={t('permanentAddress')}
                  labelEn={isFr ? t('permanentAddressEn') || undefined : undefined}
                  error={errors['permanentAddress']}
                >
                  <div data-error={!!errors['permanentAddress']}>
                    <textarea
                      value={form.permanentAddress}
                      onChange={(e) => updateField('permanentAddress', e.target.value)}
                      rows={3}
                      className={`w-full px-4 py-3 bg-beige-100 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600 resize-none ${
                        errors['permanentAddress'] ? 'border-red-500' : 'border-beige-300'
                      }`}
                    />
                  </div>
                </FormField>

                <FormField
                  label={t('passportPhoto')}
                  labelEn={isFr ? t('passportPhotoEn') || undefined : undefined}
                  error={errors['passportPhoto']}
                >
                  <div data-error={!!errors['passportPhoto']} className="flex items-center gap-3">
                    <label className={`px-4 py-2 border rounded-lg cursor-pointer hover:bg-beige-200 transition-colors text-sm ${
                      errors['passportPhoto'] ? 'border-red-500' : 'border-beige-300'
                    }`}>
                      {t('chooseFile')}
                      <input
                        type="file"
                        accept="image/*"
                        capture="environment"
                        onChange={handlePhotoChange}
                        className="hidden"
                      />
                    </label>
                    <span className="text-sm text-brown-600 truncate">
                      {passportFileName || t('noFileChosen')}
                    </span>
                  </div>
                  {passportPhoto && (
                    <img
                      src={passportPhoto}
                      alt="Passport"
                      className="mt-3 max-h-48 rounded-lg border border-beige-300"
                    />
                  )}
                </FormField>

                <FormField
                  label={`${t('dateField')} / ${t('dateFieldEn')}`}
                >
                  <input
                    type="date"
                    value={form.registrationDate}
                    onChange={(e) => updateField('registrationDate', e.target.value)}
                    className="w-full px-4 py-3 bg-beige-100 border border-beige-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600"
                  />
                </FormField>

                <FormField
                  label={t('signature')}
                  error={errors['signature']}
                >
                  <div
                    data-error={!!errors['signature']}
                    className={`border rounded-lg overflow-hidden bg-white ${
                      errors['signature'] ? 'border-red-500' : 'border-beige-300'
                    }`}
                  >
                    <SignatureCanvas
                      ref={sigCanvas}
                      penColor="#3b1a10"
                      canvasProps={{
                        className: 'w-full h-48',
                        style: { width: '100%', height: '192px' },
                      }}
                      onEnd={handleSignatureEnd}
                    />
                  </div>
                  <button
                    type="button"
                    onClick={clearSignature}
                    className="text-sm text-brown-600 hover:text-brown-800 mt-2 float-right"
                  >
                    {t('clearSignature')}
                  </button>
                  <div className="clear-both" />
                </FormField>
              </>
            )}
          </div>

          {errors['form'] && (
            <p className="text-red-600 text-sm mt-4 text-center">{errors['form']}</p>
          )}

          {/* Navigation buttons */}
          <div className="flex gap-3 mt-6">
            {step > 0 && (
              <button
                type="button"
                onClick={goPrev}
                className="flex-1 py-4 text-lg rounded-lg border-2 border-brown-700 text-brown-700 hover:bg-beige-200 transition-colors flex items-center justify-center gap-2"
              >
                <ChevronLeft className="w-5 h-5" />
                {t('previous')}
              </button>
            )}
            {step < 2 ? (
              <button
                type="button"
                onClick={goNext}
                className="flex-1 py-4 text-lg rounded-lg bg-brown-700 text-beige-50 hover:bg-brown-800 transition-colors flex items-center justify-center gap-2"
              >
                {t('next')}
                <ChevronRight className="w-5 h-5" />
              </button>
            ) : (
              <button
                type="submit"
                disabled={submitting}
                className={`flex-1 py-4 text-lg rounded-lg transition-colors ${
                  submitting
                    ? 'bg-beige-300 text-beige-400 cursor-not-allowed'
                    : 'bg-brown-700 text-beige-50 hover:bg-brown-800'
                }`}
              >
                {submitting ? '...' : t('submit')}
              </button>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}
