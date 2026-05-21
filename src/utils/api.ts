const API_BASE = import.meta.env.VITE_API_URL || '';

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = localStorage.getItem('staff_token');
  const headers: Record<string, string> = {
    ...(options.headers as Record<string, string> || {}),
  };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  if (options.body && typeof options.body === 'string') {
    headers['Content-Type'] = 'application/json';
  }

  const res = await fetch(`${API_BASE}${path}`, { ...options, headers });

  if (!res.ok) {
    const data = await res.json().catch(() => ({ detail: 'Request failed' }));
    throw new Error(data.detail || `Error ${res.status}`);
  }

  return res.json();
}

export interface StaffUser {
  id: string;
  username: string;
  email: string;
  is_admin: boolean;
  is_active: boolean;
}

export interface LoginResponse {
  access_token: string;
  token_type: string;
  user: StaffUser;
}

export const api = {
  submitRegistration(data: Record<string, unknown>) {
    return request('/api/registrations', {
      method: 'POST',
      body: JSON.stringify(data),
    });
  },

  getRegistrations(search = '', date = '') {
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (date) params.set('date', date);
    const qs = params.toString();
    return request<Record<string, unknown>[]>(`/api/registrations${qs ? `?${qs}` : ''}`);
  },

  getRegistration(id: string) {
    return request<Record<string, unknown>>(`/api/registrations/${id}`);
  },

  deleteRegistration(id: string) {
    return request(`/api/registrations/${id}`, { method: 'DELETE' });
  },

  login(username: string, password: string) {
    return request<LoginResponse>('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ username, password }),
    });
  },

  changePassword(currentPassword: string, newPassword: string) {
    return request('/api/auth/change-password', {
      method: 'POST',
      body: JSON.stringify({ current_password: currentPassword, new_password: newPassword }),
    });
  },

  forgotPassword(email: string) {
    return request('/api/auth/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email }),
    });
  },

  resetPassword(token: string, newPassword: string) {
    return request('/api/auth/reset-password', {
      method: 'POST',
      body: JSON.stringify({ token, new_password: newPassword }),
    });
  },

  getMe() {
    return request<StaffUser>('/api/auth/me');
  },

  getStats() {
    return request<{ todayArrivals: number; totalRegistrations: number }>('/api/registrations/stats/today');
  },

  createStaff(username: string, email: string, password: string, isAdmin: boolean) {
    return request<StaffUser>('/api/auth/staff', {
      method: 'POST',
      body: JSON.stringify({ username, email, password, is_admin: isAdmin }),
    });
  },

  listStaff() {
    return request<StaffUser[]>('/api/auth/staff');
  },

  deleteStaff(userId: string) {
    return request(`/api/auth/staff/${userId}`, { method: 'DELETE' });
  },
};
