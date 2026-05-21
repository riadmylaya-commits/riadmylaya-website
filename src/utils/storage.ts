import type { GuestRegistration } from '../types';

const STORAGE_KEY = 'riad_mylaya_registrations';
const STAFF_PASSWORD = 'mylaya2024';

export function getRegistrations(): GuestRegistration[] {
  const data = localStorage.getItem(STORAGE_KEY);
  if (!data) return [];
  try {
    return JSON.parse(data) as GuestRegistration[];
  } catch {
    return [];
  }
}

export function saveRegistration(registration: GuestRegistration): void {
  const registrations = getRegistrations();
  registrations.push(registration);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(registrations));
}

export function deleteRegistration(id: string): void {
  const registrations = getRegistrations().filter((r) => r.id !== id);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(registrations));
}

export function updateRegistration(updated: GuestRegistration): void {
  const registrations = getRegistrations().map((r) =>
    r.id === updated.id ? updated : r
  );
  localStorage.setItem(STORAGE_KEY, JSON.stringify(registrations));
}

export function verifyStaffPassword(password: string): boolean {
  return password === STAFF_PASSWORD;
}

export function generateId(): string {
  return Date.now().toString(36) + Math.random().toString(36).substring(2);
}
