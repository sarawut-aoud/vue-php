import { ref } from 'vue';

export function usePasswordValidation() {
  const passwordError = ref('');
  const confirmPasswordError = ref('');

  // ✅ ฟังก์ชันตรวจสอบรหัสผ่าน
  const validatePassword = (password) => {
    // เงื่อนไข: ความยาว 6-32 ตัวอักษร, มีตัวอักษร, ตัวเลข, และอักขระพิเศษ
    const regex = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[@#$!%])[A-Za-z\d@#$!%]{6,32}$/;

    if (!password) {
      passwordError.value = 'กรุณากรอกรหัสผ่าน';
      return false;
    } else if (!regex.test(password)) {
      passwordError.value =
        'รหัสผ่านต้องมีความยาว 6-32 ตัวอักษร, มีตัวอักษร, ตัวเลข และอักขระพิเศษ (@, #, !, %)';
      return false;
    }

    passwordError.value = '';
    return true;
  };

  // ✅ ฟังก์ชันตรวจสอบการยืนยันรหัสผ่าน
  const validateConfirmPassword = (password, confirmPassword) => {
    if (confirmPassword !== password) {
      confirmPasswordError.value = 'รหัสผ่านไม่ตรงกัน';
      return false;
    }

    confirmPasswordError.value = '';
    return true;
  };

  return {
    passwordError,
    confirmPasswordError,
    validatePassword,
    validateConfirmPassword,
  };
}