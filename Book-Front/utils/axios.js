import {useLocalStorage} from '@/composables/useLocalStorage';
import axios from 'axios';
const {getItem}= useLocalStorage()
axios.defaults.headers.common['X-API-KEY'] = ''
const api = axios.create({
  // baseURL: import.meta.env.VITE_API_URL, // ✅ ตั้ง Base URL ที่นี่
  timeout: 5000,                      // ⏰ Timeout (optional)
  headers: {
    'Content-Type': 'application/x-www-form-urlencoded',
  },
});

// 🔒 Interceptors (optional) — สำหรับเพิ่ม Token หรือจัดการ Error
api.interceptors.request.use(
  (config) => {
    const token = getItem('userToken'); // ⬅️ ตัวอย่างการดึง Token
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export default api;