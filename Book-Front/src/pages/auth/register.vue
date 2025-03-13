<template>
  <div id="bg-image">
    <v-card
      class="mx-auto pa-12 pb-8"
      width="600"
      elevation="8"
      rounded="lg"
      variant="elevated"
      color="white"
    >
      <v-card-text>
        <div class="text-subtitle-1 text-medium-emphasis">ชื่อที่แสดง *</div>
        <v-text-field
          v-model="username"
          density="compact"
          variant="outlined"
          hint="ชื่อที่แสดงต้องมีความยาวระหว่าง 6-32 ตัวอักษร สามารถเปลี่ยนแปลงได้ภายหลัง"
          prepend-inner-icon="mdi-account-box"
          persistent-hint
        ></v-text-field>
        <div class="text-subtitle-1 text-medium-emphasis mt-5">อีเมล *</div>
        <v-text-field
          v-model="email"
          density="compact"
          prepend-inner-icon="mdi-email-outline"
          variant="outlined"
        ></v-text-field>
        <div class="text-subtitle-1 text-medium-emphasis mt-5">รหัสผ่าน *</div>
        <v-text-field
          v-model="password"
          :append-inner-icon="visible ? 'mdi-eye-off' : 'mdi-eye'"
          :type="visible ? 'text' : 'password'"
          density="compact"
          variant="outlined"
          prepend-inner-icon="mdi-account-key"
          type="password"
          @click:append-inner="visible = !visible"
        ></v-text-field>
        <v-card variant="tonal">
          <v-card-text class="d-flex flex-column ga-2 text-caption text-medium-emphasis">
            <div>รหัสผ่านต้องมีความยาว 6-32 ตัวอักษร</div>
            <div>มีตัวอักษรอย่างน้อย 1 ตัว</div>
            <div>มีตัวเลขอย่างน้อย 1 ตัว</div>
            <div>มีอักขระพิเศษอย่างน้อย 1 ตัว เช่น @, #, !, %</div>
          </v-card-text>
        </v-card>
        <div class="text-subtitle-1 text-medium-emphasis mt-5">ยืนยันรหัสผ่าน *</div>
        <v-text-field
          v-model="confirmPassword"
          :append-inner-icon="visible_confirm ? 'mdi-eye-off' : 'mdi-eye'"
          :type="visible_confirm ? 'text' : 'password'"
          density="compact"
          variant="outlined"
          prepend-inner-icon="mdi-account-key"
          type="password"
          @click:append-inner="visible_confirm = !visible_confirm"
        ></v-text-field>
        <p v-if="confirmPasswordError" class="text-red">{{ confirmPasswordError }}</p>
        <p v-if="passwordError" class="text-red">{{ passwordError }}</p>
      </v-card-text>
      <v-card-text class="text-center mt-4">
        <v-btn
          @click="handleSubmit"
          :loading="isLoading"
          :disabled="isLoading"
          variant="flat"
          block
          rounded="lg"
          color="primary"
        >
          {{ isLoading ? "กำลังตรวจสอบ..." : "ยืนยัน" }}</v-btn
        >
        <v-btn
          href="login"
          variant="text"
          block
          rounded="lg"
          color="primary"
          class="mt-5"
        >
          เข้าสู่ระบบ</v-btn
        >
      </v-card-text>
    </v-card>
  </div>
  <Notivue v-slot="item">
    <Notification :item="item" />
  </Notivue>
</template>
<script setup>
import { ref } from "vue";
import api from "/utils/axios";
import { useCookie } from "@/composables/useCookie";
import { usePasswordValidation } from "@/composables/usePasswordValidation";
import { useLocalStorage } from "@/composables/useLocalStorage";
import { Notivue, Notification, push } from "notivue";
const { getCookie, setCookie, deleteCookie } = useCookie();
const {
  passwordError,
  confirmPasswordError,
  validatePassword,
  validateConfirmPassword,
} = usePasswordValidation();
const { setItem } = useLocalStorage();
const password = ref("");
const email = ref("");
const username = ref("");
const confirmPassword = ref("");
const visible_confirm = ref("");
const visible = ref("");
let isLoading = ref(false);

let handleSubmit = async () => {
  isLoading.value = true;
  const isPasswordValid = validatePassword(password.value);
  const isConfirmPasswordValid = validateConfirmPassword(
    password.value,
    confirmPassword.value
  );
  if (isPasswordValid && isConfirmPasswordValid) {
    let { data } = await api.post("/api/auth/authen/register", {
      username: username.value,
      email: email.value,
      password: password.value,
      csrf_token_ci_gen: getCookie("csrf_cookie_ci_gen"),
    });
    if (data?.success) {
      setItem("userToken", data?.data?.token_key);
      setCookie("jwt", data?.data?.jwt);
      push.success({
        title: "Successfuly !!",
        message: "ลงทะเบียนสำเร็จ",
      });
      setTimeout(() => {
        window.location.href = "/";
      }, 1000);
    } else {
      push.error({
        title: "ERROR",
        message: data?.data?.msg,
      });
    }
  } else {
    push.error({
      title: "ERROR",
      message: passwordError ? passwordError : confirmPasswordError,
    });
  }
  setTimeout(() => {
    isLoading.value = false;
  }, 400);
};
</script>
<style>
#bg-image {
  background-image: url(/src/assets/login/bg-01.jpg);
  background-position: center;
  background-size: cover;
  background-repeat: no-repeat;
  width: 100%;
  min-height: 100vh;
  display: -webkit-box;
  display: -webkit-flex;
  display: -moz-box;
  display: -ms-flexbox;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  padding: 15px;
}
</style>
