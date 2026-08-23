<template>
  <div class="faq-accordion">
    <details
      v-for="(item, index) in normalizedItems"
      :key="getItemKey(item, index)"
      :ref="(element) => setItemRef(element, index)"
      class="faq-item"
      @toggle="handleToggle(index)"
    >
      <summary class="faq-question" :style="questionStyle">{{ item.question || 'سوال' }}</summary>
      <div class="faq-answer whitespace-pre-line" :style="answerStyle">{{ item.answer || 'پاسخ' }}</div>
    </details>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { getFontFamily, normalizeTypography } from '../../utils/digitalBusinessCardTypography';

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  typography: {
    type: Object,
    default: () => ({}),
  },
  blockId: {
    type: [Number, String],
    default: '',
  },
  allowMultipleOpen: {
    type: Boolean,
    default: true,
  },
});

const normalizedItems = computed(() => (props.items || []).filter((item) => item && (item.question || item.answer)));
const normalizedTypography = computed(() => normalizeTypography(props.typography));
const questionStyle = computed(() => {
  const fontFamily = getFontFamily(normalizedTypography.value.faq_question_font || null);
  return fontFamily ? { fontFamily } : {};
});
const answerStyle = computed(() => {
  const fontFamily = getFontFamily(normalizedTypography.value.faq_answer_font || null);
  return fontFamily ? { fontFamily } : {};
});
const itemRefs = [];

function getItemKey(item, index) {
  return [props.blockId, item.id || item.key || item.uuid || index].filter(Boolean).join('-');
}

function setItemRef(element, index) {
  if (element) {
    itemRefs[index] = element;
  }
}

function handleToggle(index) {
  if (props.allowMultipleOpen || !itemRefs[index]?.open) {
    return;
  }

  itemRefs.forEach((itemRef, itemIndex) => {
    if (itemIndex !== index && itemRef) {
      itemRef.open = false;
    }
  });
}
</script>

<style scoped>
.faq-accordion {
  display: grid;
  gap: 0.75rem;
  direction: rtl;
}

.faq-item {
  border-radius: 0.75rem;
  background: rgb(15 23 42 / 0.06);
  overflow: hidden;
}

.faq-question {
  list-style: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.9rem 1rem;
  font-weight: 700;
  color: inherit;
}

.faq-question::-webkit-details-marker {
  display: none;
}

.faq-question::after {
  content: '+';
  font-size: 1.1rem;
  line-height: 1;
  opacity: 0.8;
}

.faq-item[open] .faq-question::after {
  content: '−';
}

.faq-answer {
  padding: 0 1rem 1rem;
  font-size: 0.95rem;
  line-height: 1.8;
}
</style>
