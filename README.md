# Riad Mylaya — Fiche de Police / Guest Registration

Digital guest registration platform for Riad Mylaya, Marrakech.

## Features

- **Homepage** — Welcome page with riad branding and navigation
- **Guest Registration Form** — Complete police form with all required fields, passport photo upload, electronic signature
- **Multi-language** — French, English, Spanish with automatic interface switching
- **QR Code Poster** — Printable poster with QR code for guest self-registration
- **Staff Area** — Password-protected dashboard with search, PDF download, Excel export, and daily statistics
- **Validation** — All fields mandatory, submit button disabled until complete
- **Responsive** — Mobile-first design, works on all devices

## Tech Stack

- React + TypeScript + Vite
- Tailwind CSS v4
- React Router, react-i18next, react-signature-canvas
- QR Code generation, jsPDF, xlsx export
- localStorage for data persistence

## Development

```bash
npm install
npm run dev
```

## Build

```bash
npm run build
```

## Staff Access

Default password: `mylaya2024`
