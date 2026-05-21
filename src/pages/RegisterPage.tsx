import { useState, useRef, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTranslation } from 'react-i18next';
import SignatureCanvas from 'react-signature-canvas';
import { ArrowLeft, CheckCircle } from 'lucide-react';
import LanguageSelector from '../components/LanguageSelector';
import FormField from '../components/FormField';
import { api } from '../utils/api';

const today = () => new Date().toISOString().split('T')[0];

export default function RegisterPage() {
  const { t, i18n } = useTranslation();
  const navigate = useNavigate();
  const sigCanvas = useRef<SignatureCanvas>(null);
  const isFr = i18n.language === 'fr';

  const [form, setForm] = useState({
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
  });

  const [passportPhoto, setPassportPhoto] = useState<string>('');
  const [passportFileName, setPassportFileName] = useState<string>('');
  const [signatureData, setSignatureData] = useState<string>('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [attemptedSubmit, setAttemptedSubmit] = useState(false);

  const updateField = (field: string, value: string | number) => {
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

  const validate = useCallback((): Record<string, string> => {
    const errs: Record<string, string> = {};
    const requiredText = t('requiredField');

    const requiredFields: (keyof typeof form)[] = [
      'room', 'lastName', 'firstName', 'dateOfBirth', 'placeOfBirth',
      'nationality', 'occupation', 'cinNumber', 'moroccoEntryNumber',
      'arrivalDate', 'departureDate', 'comingFrom', 'goingTo',
      'passportNumber', 'passportIssueDate', 'passportIssuePlace',
      'permanentAddress',
    ];

    for (const field of requiredFields) {
      const val = form[field];
      if (val === '' || val === undefined || val === null) {
        errs[field] = requiredText;
      }
    }

    if (!passportPhoto) {
      errs['passportPhoto'] = t('requiredPhoto');
    }

    if (!signatureData) {
      errs['signature'] = t('requiredSignature');
    }

    return errs;
  }, [form, passportPhoto, signatureData, t]);

  const isFormValid = useCallback((): boolean => {
    return Object.keys(validate()).length === 0;
  }, [validate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setAttemptedSubmit(true);

    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      const firstErrorField = document.querySelector('[data-error="true"]');
      firstErrorField?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    setSubmitting(true);
    try {
      await api.submitRegistration({
        ...form,
        passportPhoto,
        signature: signatureData,
      });
      setSubmitted(true);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch {
      setErrors({ form: t('submitError') || 'Submission failed. Please try again.' });
    } finally {
      setSubmitting(false);
    }
  };

  if (submitted) {
    return (
      <div className="min-h-screen bg-beige-50 flex items-center justify-center p-6">
        <div className="text-center max-w-md">
          <CheckCircle className="w-16 h-16 text-green-600 mx-auto mb-6" />
          <h2 className="text-2xl font-serif text-brown-900 mb-4">
            {t('successMessage')}
          </h2>
          {isFr && (
            <p className="text-brown-600 mb-6">{t('successMessageEn')}</p>
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

  const renderInput = (field: string, labelKey: string, labelEnKey: string, type = 'text') => (
    <FormField
      label={t(labelKey)}
      labelEn={isFr ? t(labelEnKey) || undefined : undefined}
      error={errors[field]}
    >
      <div data-error={!!errors[field]}>
        <input
          type={type}
          value={form[field as keyof typeof form] as string}
          onChange={(e) => updateField(field, e.target.value)}
          className={`w-full px-4 py-3 bg-beige-100 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600 ${
            errors[field] ? 'border-red-500' : 'border-beige-300'
          }`}
        />
      </div>
    </FormField>
  );

  const renderDateInput = (field: string, labelKey: string, labelEnKey: string) => (
    <FormField
      label={t(labelKey)}
      labelEn={isFr ? t(labelEnKey) || undefined : undefined}
      error={errors[field]}
    >
      <div data-error={!!errors[field]}>
        <input
          type="date"
          value={form[field as keyof typeof form] as string}
          onChange={(e) => updateField(field, e.target.value)}
          className={`w-full px-4 py-3 bg-beige-100 border rounded-lg focus:outline-none focus:ring-2 focus:ring-brown-600 ${
            errors[field] ? 'border-red-500' : 'border-beige-300'
          }`}
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

        <div className="text-center mb-8">
          <p className="text-xs tracking-[0.25em] text-brown-600 uppercase mb-2">
            RIAD MYLAYA
          </p>
          <h1 className="text-3xl md:text-4xl font-serif text-brown-900 mb-1">
            {t('formTitle')}
          </h1>
          <p className="text-brown-600">{t('formSubtitle')}</p>
        </div>

        <form onSubmit={handleSubmit} noValidate>
          <div className="bg-white/60 border border-beige-200 rounded-2xl p-6 md:p-8">
            {renderInput('room', 'room', 'roomEn')}
            {renderInput('lastName', 'lastName', 'lastNameEn')}
            {renderInput('firstName', 'firstName', 'firstNameEn')}
            {renderDateInput('dateOfBirth', 'dateOfBirth', 'dateOfBirthEn')}
            {renderInput('placeOfBirth', 'placeOfBirth', 'placeOfBirthEn')}
            {renderInput('nationality', 'nationality', 'nationalityEn')}
            {renderInput('occupation', 'occupation', 'occupationEn')}
            {renderInput('cinNumber', 'cinNumber', 'cinNumberEn')}
            {renderInput('moroccoEntryNumber', 'moroccoEntryNumber', 'moroccoEntryNumberEn')}
            {renderDateInput('arrivalDate', 'arrivalDate', 'arrivalDateEn')}
            {renderDateInput('departureDate', 'departureDate', 'departureDateEn')}

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
          </div>

          {errors['form'] && (
            <p className="text-red-600 text-sm mt-4 text-center">{errors['form']}</p>
          )}

          <button
            type="submit"
            disabled={submitting || (attemptedSubmit && !isFormValid())}
            className={`w-full mt-6 py-4 text-lg rounded-lg transition-colors ${
              submitting || (attemptedSubmit && !isFormValid())
                ? 'bg-beige-300 text-beige-400 cursor-not-allowed'
                : 'bg-brown-700 text-beige-50 hover:bg-brown-800'
            }`}
          >
            {submitting ? '...' : t('submit')}
          </button>
        </form>
      </div>
    </div>
  );
}
