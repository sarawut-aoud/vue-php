<script setup>
import { ref, defineProps } from "vue";
import api from "/utils/axios";

const props = defineProps({
  existingImages: Array, // รับรูปภาพจาก Parent
});
const selectedFiles = ref([]);

// 📌 ฟังก์ชันเมื่อผู้ใช้เลือกไฟล์
const onFileChange = (event) => {
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    if (file.type.startsWith("image/")) {
      const reader = new FileReader();
      reader.onload = (e) => {
        selectedFiles.value.push({ file, preview: e.target.result });
      };
      reader.readAsDataURL(file);
    }
  });
};

// 📌 ฟังก์ชันลบไฟล์ที่เลือก
const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

// 📌 อ้างอิง input file แบบซ่อน
const fileInput = ref(null);
const openFilePicker = () => {
  fileInput.value.click();
};
const getFiles = () => {
  return selectedFiles.value.map((item) => item.file);
};

const removeFileDB = async (id, index) => {
  props.existingImages.splice(index, 1);
  await api.get("/api/products/removePicture/" + id);
};

// 📌 ให้ component อื่นสามารถเรียก getFiles()
defineExpose({ getFiles });
</script>

<template>
  <v-card rounded="lg" flat>
    <v-card-text>
      <input
        type="file"
        ref="fileInput"
        accept="image/*"
        multiple
        hidden
        @change="onFileChange"
      />

      <!-- ปุ่มเพิ่มรูป -->
      <v-btn color="primary" @click="openFilePicker">
        <v-icon left>mdi-plus</v-icon> เพิ่มรูป
      </v-btn>
      <div class="py-4" style="font-size: 16px">รูปใหม่</div>
      <div v-if="selectedFiles.length == 0">ยังไม่ได้เลือกรูปเพิ่ม</div>
      <!-- แสดงรูปที่อัปโหลด -->
      <v-row class="mt-4">
        <v-col
          v-for="(item, index) in selectedFiles"
          :key="index"
          cols="12"
          sm="4"
          md="3"
        >
          <v-card>
            <v-img :src="item.preview" cover width="500"></v-img>
            <v-card-actions>
              <v-btn color="red" @click="removeFile(index)" block>
                <v-icon>mdi-delete</v-icon> ลบรูปนี้
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
      <!-- แสดงรูปที่ส่งมาจาก Parent -->
      <div class="py-4" style="font-size: 16px">รูปเก่า</div>
      <v-row v-if="existingImages?.length > 0">
        <v-col v-for="(img, index) in existingImages" :key="index" cols="12" sm="4">
          <v-card>
            <v-img :src="img.path" cover width="500"></v-img>
            <v-card-actions>
              <v-btn color="red" @click="removeFileDB(img._i, index)" block>
                <v-icon>mdi-delete</v-icon> ลบรูปนี้
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>
