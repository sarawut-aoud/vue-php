// src/composables/useLocalStorage.js

import { ref } from 'vue';

export function useLocalStorage() {
  const storageError = ref('');

  // ✅ Get Item จาก localStorage
  const getItem = (key) => {
    try {
      const value = localStorage.getItem(key);
      return value ? JSON.parse(value) : null;
    } catch (error) {
      storageError.value = 'ไม่สามารถอ่านข้อมูลจาก localStorage';
      console.error(error);
      return null;
    }
  };

  // ✅ Set Item ลงใน localStorage
  const setItem = (key, value) => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
      storageError.value = '';
    } catch (error) {
      storageError.value = 'ไม่สามารถบันทึกข้อมูลลง localStorage';
      console.error(error);
    }
  };

  // ✅ Delete Item ออกจาก localStorage
  const deleteItem = (key) => {
    try {
      localStorage.removeItem(key);
      storageError.value = '';
    } catch (error) {
      storageError.value = 'ไม่สามารถลบข้อมูลจาก localStorage';
      console.error(error);
    }
  };

  // ✅ Clear ทั้งหมดใน localStorage (optional)
  const clearStorage = () => {
    try {
      localStorage.clear();
      storageError.value = '';
    } catch (error) {
      storageError.value = 'ไม่สามารถเคลียร์ localStorage';
      console.error(error);
    }
  };
  const isItem = (key)=>{
    const value = localStorage.getItem(key);
    return value ? true:false
  }

  return {
    storageError,
    isItem,
    getItem,
    setItem,
    deleteItem,
    clearStorage,
  };
}