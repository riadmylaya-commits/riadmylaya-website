import { BrowserRouter, Routes, Route } from 'react-router-dom';
import HomePage from './pages/HomePage';
import RegisterPage from './pages/RegisterPage';
import QRCodePage from './pages/QRCodePage';
import StaffPage from './pages/StaffPage';

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/qr" element={<QRCodePage />} />
        <Route path="/staff" element={<StaffPage />} />
      </Routes>
    </BrowserRouter>
  );
}
