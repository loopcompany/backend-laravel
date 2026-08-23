<template>
  <div class="min-h-screen bg-slate-100 text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
      
        <div class="flex flex-wrap gap-2" style="font-family: 'LoopCardFont', sans-serif;direction:rtl">
          <button
            v-if="createCardUrl"
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-300"
            @click="goToCreateCard"
          >
            کارت جدید
          </button>

          <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="saving || loading"
            @click="saveCard"
          >
            {{ saving ? 'در حال ذخیره...' : 'ذخیره' }}
          </button>
        </div>
        <div style="font-family: 'LoopCardFont', sans-serif;direction:rtl">
          <p class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500 text-right">کارت ویزیت دیجیتال</p>
          <h1 class="mt-1 text-3xl font-bold text-slate-900 text-right" >ویرایشگر کارت</h1>
          <p class="mt-2 text-sm text-slate-600 text-right">فیلدهای کارت را ویرایش، پیش‌نمایش و با یک درخواست ذخیره کنید.</p>
        </div>

      </div>

      <div v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-6 text-slate-600 shadow-sm" style="font-family: 'LoopCardFont', sans-serif;direction:rtl">
        در حال بارگذاری اطلاعات ویرایشگر...
      </div>

      <div v-else class="grid gap-6 lg:grid-cols-2" style="font-family: 'LoopCardFont', sans-serif;">
        <section class="space-y-6">
          <div v-if="flashMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800" style="direction: rtl;">
            {{ flashMessage }}
          </div>

          <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"  style="direction: rtl;">
            {{ errorMessage }}
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900 text-right">فیلدهای کارت</h2>
            <div class="mt-4 grid gap-4">
              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 text-right">تنظیمات هدر</h3>
                <div class="mt-4 grid gap-4 text-right">
                  <Field label="عنوان">
                    <input
                      v-model="card.title"
                      type="text"
                      class="editor-input text-right"
                      @pointerdown.stop
                      @mousedown.stop
                      @touchstart.stop
                      @click.stop
                    />
                  </Field>
                  <Field label="اسلاگ">
                    <input
                      v-model="card.slug"
                      type="text"
                      class="editor-input text-right"
                      @pointerdown.stop
                      @mousedown.stop
                      @touchstart.stop
                      @click.stop
                    />
                  </Field>

                  <Field label="توضیحات">
                    <textarea
                      v-model="card.des"
                      rows="3"
                      class="editor-input text-right"
                      @pointerdown.stop
                      @mousedown.stop
                      @touchstart.stop
                      @click.stop
                    ></textarea>
                  </Field>

                  <Field label="رنگ نوشته های هدر">
                    <div class="flex items-center gap-3">
                      <input
                        v-model="card.header_text_color"
                        type="color"
                        class="color-input text-right"
                        @pointerdown.stop
                        @mousedown.stop
                        @touchstart.stop
                        @click.stop
                      />
                      <span class="text-sm font-medium text-slate-600">{{ resolvedHeaderTextColor }}</span>
                    </div>
                  </Field>

                  <div class="grid gap-4 md:grid-cols-2">
                    <Field label="فونت عنوان کارت">
                      <select
                        v-model="card.title_font_family"
                        class="editor-input"
                        @pointerdown.stop
                        @mousedown.stop
                        @touchstart.stop
                        @click.stop
                      >
                        <option value="">پیش‌فرض</option>
                        <option
                          v-for="font in fontOptions"
                          :key="font.value"
                          :value="font.value"
                        >
                          {{ font.label }}
                        </option>
                      </select>
                    </Field>

                    <Field label="فونت توضیحات کارت">
                      <select
                        v-model="card.description_font_family"
                        class="editor-input"
                        @pointerdown.stop
                        @mousedown.stop
                        @touchstart.stop
                        @click.stop
                      >
                        <option value="">پیش‌فرض</option>
                        <option
                          v-for="font in fontOptions"
                          :key="font.value"
                          :value="font.value"
                        >
                          {{ font.label }}
                        </option>
                      </select>
                    </Field>
                  </div>

                  <div class="grid gap-4 md:grid-cols-2">
                    <Field label="لوگو">
                      <div class="space-y-3">
                        <input
                          type="file"
                          accept="image/*"
                          class="editor-file"
                          :disabled="uploadingField === 'logo'"
                          @change="uploadMedia('logo', $event, 'logo')"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                        <div v-if="mediaSrc(card.logo)" class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                          <img :src="mediaSrc(card.logo)" alt="Logo preview" class="h-20 w-20 rounded-2xl object-cover" />
                        </div>
                      </div>
                    </Field>

                    <Field label="تصویر زمینه ی هدر">
                      <div class="space-y-3">
                        <input
                          type="file"
                          accept="image/*"
                          class="editor-file"
                          :disabled="uploadingField === 'image_background'"
                          @change="uploadMedia('image_background', $event, 'image_background')"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                        <div v-if="mediaSrc(card.image_background)" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                          <img :src="mediaSrc(card.image_background)" alt="Header background preview" class="h-28 w-full object-cover" />
                        </div>
                      </div>
                    </Field>
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500 text-right">تصویر زمینه</h3>
                <div class="mt-4 grid gap-4 text-right">
                  <Field label="تصویر زمینه ی سراسری">
                    <div class="space-y-3">
                      <input
                        type="file"
                        accept="image/*"
                        class="editor-file"
                        :disabled="uploadingField === 'page_background_image'"
                        @change="uploadMedia('page_background_image', $event, 'page_background_image')"
                        @pointerdown.stop
                        @mousedown.stop
                        @touchstart.stop
                        @click.stop
                      />
                      <div v-if="mediaSrc(card.page_background_image)" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                        <img :src="mediaSrc(card.page_background_image)" alt="Page background preview" class="h-28 w-full object-cover" />
                      </div>
                    </div>
                  </Field>
                </div>
              </div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm text-right">
            <div class="flex flex-wrap items-center justify-end gap-3 text-right">
              <h2 class="text-lg font-semibold text-slate-900 text-right">بلاک‌ها</h2>
              <div class="flex flex-wrap flex-row-reverse gap-2 text-right">
                <button v-for="type in blockTypes" :key="type.key" type="button" class="add-block-btn" @click="addBlock(type.key)">
                  افزودن {{ type.label }}
                </button>
              </div>
            </div>

            <div class="mt-5 space-y-4">
              <article
                v-for="(block, index) in card.blocks"
                :key="block.key"
                class="block-card rounded-2xl border bg-slate-50 p-4"
                :class="draggedBlockIndex === index ? 'is-dragging' : dropTargetIndex === index ? 'is-drop-target' : ''"
                :style="blockCardStyle(block)"
                @dragover.prevent="setDropTarget(index)"
                @drop.prevent="dropBlock(index)"
              >
                <div class="flex flex-wrap items-center flex-row-reverse justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <span
                      class="drag-handle cursor-grab active:cursor-grabbing"
                      draggable="true"
                      title="جابجایی"
                      @dragstart="startBlockDrag(index)"
                      @dragend="endBlockDrag"
                    >⋮⋮</span>
                    <div>
                      <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ block.type }}</p>
                      <h3 class="text-base font-semibold text-slate-900">بلوک #{{ block.sort }}</h3>
                    </div>
                  </div>

                  <button type="button" class="danger-btn" @click.stop="removeBlock(index)">حذف بلوک</button>
                </div>

                <div class="mt-4 grid gap-4 no-drag" >
                  <Field label="عنوان" v-if="block.type !== 'text'">
                    <input
                      v-model="block.title"
                      type="text"
                      class="editor-input"
                      @pointerdown.stop
                      @mousedown.stop
                      @touchstart.stop
                      @click.stop
                    />
                  </Field>

                  <Field label="رنگ">
                    <div class="flex items-center gap-3">
                      <input
                        v-model="block.color"
                        type="color"
                        class="color-input"
                        @pointerdown.stop
                        @mousedown.stop
                        @touchstart.stop
                        @click.stop
                      />
                      <span class="text-sm font-medium text-slate-600">{{ block.color }}</span>
                    </div>
                  </Field>

                  <Field label="رنگ زمینه">
                    <div class="space-y-3 no-drag">
                      <div class="flex items-center gap-3">
                        <input
                          type="color"
                          class="h-10 w-12 cursor-pointer rounded border border-slate-200 bg-white"
                          :value="getColorInputValue(block.background_color)"
                          @input="updateBlockBackgroundColor(
                            block,
                            $event.target.value,
                            getBackgroundOpacity(block.background_color)
                          )"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />

                        <div class="flex flex-col">
                          <span class="text-sm font-medium text-slate-700">
                            رنگ
                          </span>

                          <span class="text-xs text-slate-500 ltr text-left">
                            {{ getColorInputValue(block.background_color) }}
                          </span>
                        </div>
                      </div>

                      <div class="space-y-2">
                        <div class="flex items-center justify-between gap-3">
                          <span class="text-sm font-medium text-slate-700">
                            شفافیت
                          </span>

                          <span class="text-xs font-semibold text-slate-500">
                            {{ Math.round(getBackgroundOpacity(block.background_color) * 100) }}%
                          </span>
                        </div>

                        <input
                          type="range"
                          min="0"
                          max="100"
                          step="1"
                          class="w-full accent-slate-900"
                          :value="Math.round(getBackgroundOpacity(block.background_color) * 100)"
                          @input="updateBlockBackgroundColor(
                            block,
                            getColorInputValue(block.background_color),
                            Number($event.target.value) / 100
                          )"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                      </div>

                      <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 bg-slate-50 p-3">
                        <div class="flex items-center gap-2">
                          <span
                            class="h-7 w-7 rounded-lg border border-slate-200 shadow-sm"
                            :style="{ backgroundColor: normalizeCssColor(block.background_color) || 'transparent' }"
                          />

                          <span class="text-xs font-medium text-slate-600 ltr text-left">
                            {{ formatColorLabel(block.background_color) }}
                          </span>
                        </div>

                        <button
                          type="button"
                          class="text-xs font-semibold text-slate-500 hover:text-rose-600"
                          @click.stop="block.background_color = ''"
                        >
                          حذف رنگ
                        </button>
                      </div>
                    </div>
                  </Field>

                  <template v-if="typographyFieldsForBlock(block).length">
                    <div class="rounded-xl border border-slate-200 bg-white p-3 no-drag">
                      <h4 class="mb-3 text-sm font-semibold text-slate-700">تنظیمات فونت</h4>
                      <div class="grid gap-3 md:grid-cols-2">
                        <Field
                          v-for="field in typographyFieldsForBlock(block)"
                          :key="`${block.key}-${field.key}`"
                          :label="field.label"
                        >
                          <!-- Font select -->
                          <select
                            v-if="field.type === 'font' || !field.type"
                            v-model="block.data.typography[field.key]"
                            class="editor-input"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          >
                            <option
                              v-for="font in fontOptions"
                              :key="`${field.key}-${font.value || 'default'}`"
                              :value="font.value"
                            >
                              {{ font.label }}
                            </option>
                          </select>

                          <!-- Size input -->
                          <input
                            v-else-if="field.type === 'size'"
                            v-model.number="block.data.typography[field.key]"
                            type="number"
                            min="10"
                            max="72"
                            class="editor-input"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          />

                          <!-- Align select -->
                          <select
                            v-else-if="field.type === 'align'"
                            v-model="block.data.typography[field.key]"
                            class="editor-input"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          >
                            <option
                              v-for="align in ALIGN_OPTIONS"
                              :key="`${field.key}-${align.value}`"
                              :value="align.value"
                            >
                              {{ align.label }}
                            </option>
                          </select>
                        </Field>
                      </div>
                    </div>
                  </template>

                  <template v-if="block.type === 'text'">
                    <div class="no-drag">
                      <Field label="توضیحات">
                        <textarea
                          v-model="block.data.descriptions"
                          rows="4"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        ></textarea>
                      </Field>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'link'">
                    <div class="no-drag">
                      <Field label="لینک">
                        <input
                          v-model="block.data.link"
                          type="text"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                      </Field>

                      <Field label="تصویر">
                        <div class="space-y-3">
                          <input
                            type="file"
                            accept="image/*"
                            class="editor-file"
                            :disabled="uploadingField === `block-${block.key}`"
                            @change="uploadBlockImage(block, $event)"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          />
                          <div v-if="mediaSrc(block.data.image)" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                            <img :src="mediaSrc(block.data.image)" alt="Block image preview" class="h-28 w-full object-cover" />
                          </div>
                          <div v-if="mediaSrc(block.data.image)" class="flex items-center gap-2">
                            <button type="button" class="danger-btn" @click="removeBlockImage(block)">حذف تصویر</button>
                          </div>
                        </div>
                      </Field>

                      <Field label="انیمیشن">
                        <select
                          v-model="block.data.animation_type"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        >
                          <option v-for="option in animationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                      </Field>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'social'">
                    <div class="no-drag">
                      <Field label="لینک">
                        <input
                          v-model="block.data.link"
                          type="text"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                      </Field>
                      <Field label="آیکون">
                        <div class="space-y-3">
                          <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp,image/svg+xml"
                            class="editor-file"
                            :disabled="uploadingField === `icon-${block.key}`"
                            @change="uploadBlockIcon(block, $event)"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          />
                          <div v-if="iconSrc(block.data.icon)" class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                            <img :src="iconSrc(block.data.icon)" alt="پیش‌نمایش آیکون" class="block-icon-preview" />
                            <button type="button" class="danger-btn" @click="removeBlockIcon(block)">حذف آیکون</button>
                          </div>
                        </div>
                      </Field>
                      <Field label="انیمیشن">
                        <select
                          v-model="block.data.animation_type"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        >
                          <option v-for="option in animationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                      </Field>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'map'">
                    <div class="no-drag">
                      <Field label="آدرس">
                        <input
                          v-model="block.data.address"
                          type="text"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                      </Field>
                      <Field label="لینک">
                        <input
                          v-model="block.data.link"
                          type="text"
                          class="editor-input"
                          @pointerdown.stop
                          @mousedown.stop
                          @touchstart.stop
                          @click.stop
                        />
                      </Field>
                      <div class="grid gap-4 md:grid-cols-2">
                        <Field label="عرض جغرافیایی">
                          <input
                            v-model="block.data.latitude"
                            type="number"
                            step="any"
                            class="editor-input"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          />
                        </Field>
                        <Field label="طول جغرافیایی">
                          <input
                            v-model="block.data.longitude"
                            type="number"
                            step="any"
                            class="editor-input"
                            @pointerdown.stop
                            @mousedown.stop
                            @touchstart.stop
                            @click.stop
                          />
                        </Field>
                      </div>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'faq'">
                    <div class="no-drag">
                      <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-slate-800">آیتم های سوالات متداول</h4>
                        <button type="button" class="add-item-btn" @click="addFaqItem(block)">افزودن سوال</button>
                      </div>

                      <div class="space-y-3">
                        <div v-for="(item, itemIndex) in block.data.items" :key="item.key" class="rounded-xl border border-slate-200 bg-white p-4">
                          <div class="grid gap-3 md:grid-cols-1">
                            <Field label="سوال">
                              <input
                                v-model="item.question"
                                type="text"
                                class="editor-input"
                                @pointerdown.stop
                                @mousedown.stop
                                @touchstart.stop
                                @click.stop
                              />
                            </Field>
                            <Field label="جواب">
                              <input
                                v-model="item.answer"
                                type="text"
                                class="editor-input"
                                @pointerdown.stop
                                @mousedown.stop
                                @touchstart.stop
                                @click.stop
                              />
                            </Field>
                          </div>
                          <div class="mt-3 flex items-end gap-3">
                            <Field label="ترتیب" class="flex-1">
                              <input
                                v-model.number="item.sort"
                                type="number"
                                min="1"
                                class="editor-input"
                                @pointerdown.stop
                                @mousedown.stop
                                @touchstart.stop
                                @click.stop
                              />
                            </Field>
                            <button type="button" class="danger-btn h-11" :disabled="block.data.items.length === 1" @click="removeFaqItem(block, itemIndex)">حذف</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>

                  <template v-else-if="block.type === 'gallery'">
                    <div class="no-drag">
                      <div class="flex items-center justify-between gap-3">
                        <h4 class="text-sm font-semibold text-slate-800">تصاویر گالری</h4>
                        <button type="button" class="add-item-btn" @click="addGalleryItem(block)">افزودن تصویر</button>
                      </div>

                      <div class="space-y-3">
                        <div v-for="(item, itemIndex) in block.data.items" :key="item.key" class="rounded-xl border border-slate-200 bg-white p-4">
                          <div class="grid gap-3 md:grid-cols-2">
                            <Field label="تصویر">
                              <div class="space-y-3">
                                <input
                                  type="file"
                                  accept="image/*"
                                  class="editor-file"
                                  :disabled="uploadingField === `gallery-${block.key}-${item.key}`"
                                  @change="uploadGalleryImage(block, item, $event)"
                                  @pointerdown.stop
                                  @mousedown.stop
                                  @touchstart.stop
                                  @click.stop
                                />
                                <div v-if="mediaSrc(item.image)" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                  <img :src="mediaSrc(item.image)" alt="Gallery preview" class="h-28 w-full object-cover" />
                                </div>
                                <div v-if="mediaSrc(item.image)" class="flex items-center gap-2">
                                  <button type="button" class="danger-btn" @click="removeGalleryItem(block, itemIndex)">حذف تصویر</button>
                                </div>
                              </div>
                            </Field>
                            <Field label="کپشن">
                              <input
                                v-model="item.caption"
                                type="text"
                                class="editor-input"
                                @pointerdown.stop
                                @mousedown.stop
                                @touchstart.stop
                                @click.stop
                              />
                            </Field>
                          </div>
                          <div class="mt-3 flex items-end gap-3">
                            <Field label="ترتیب" class="flex-1">
                              <input
                                v-model.number="item.sort"
                                type="number"
                                min="1"
                                class="editor-input"
                                @pointerdown.stop
                                @mousedown.stop
                                @touchstart.stop
                                @click.stop
                              />
                            </Field>
                            <button type="button" class="danger-btn h-11" :disabled="block.data.items.length === 1" @click="removeGalleryItem(block, itemIndex)">حذف</button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </article>
            </div>
          </div>
        </section>

        <aside class="lg:sticky lg:top-6 h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-900">پیش نمایش</h2>
            <!-- MVP badge removed per localization request -->
          </div>

          <div class="mt-4 flex justify-center">
            <div class="mobile-preview-frame">
              <div class="mobile-preview-notch"></div>
              <div class="mobile-preview-screen" :style="pageBackgroundStyle">
                <div class="mobile-preview-content">
                  <section class="preview-header" :style="headerBackgroundStyle">
                    <div class="preview-header-content p-4" :style="{ color: resolvedHeaderTextColor }">
                      <div v-if="mediaSrc(card.logo)" class="mx-auto mb-3 h-16 w-16 overflow-hidden rounded-full bg-transparent">
                        <img :src="mediaSrc(card.logo)" alt="Logo" class="block h-full w-full rounded-full object-cover object-center" />
                      </div>
                      <h3 class="text-2xl font-bold" :style="getCardTitleStyle">{{ card.title || 'Card title' }}</h3>
                      <p class="mt-2 text-sm whitespace-pre-line" :style="getCardDescriptionStyle">{{ card.des || 'Card description' }}</p>
                    </div>
                  </section>

                  <section class="preview-blocks space-y-4 p-4 text-slate-100">
                    <article
                      v-for="(block, index) in card.blocks"
                      :key="'preview-' + block.key"
                      class="block-preview digital-card-preview rounded-2xl p-4"
                      :class="draggedBlockIndex === index ? 'is-dragging' : dropTargetIndex === index ? 'is-drop-target' : ''"
                      :style="previewBlockStyle(block)"
                      @dragover.prevent="setDropTarget(index)"
                      @drop.prevent="dropBlock(index)"
                    >
                        <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                          <span
                            class="drag-handle cursor-grab active:cursor-grabbing"
                            draggable="true"
                            title="جابه‌جایی"
                            @dragstart="startBlockDrag(index)"
                            @dragend="endBlockDrag"
                          >⋮⋮</span>
                          <h4>
                            <a
                              v-if="previewBlockLink(block)"
                              :href="previewBlockLink(block)"
                              target="_blank"
                              rel="noopener noreferrer"
                              class="preview-link-title"
                              :style="typographyStyleFor(block, 'title_font')"
                            >
                              {{ block.title || blockTypeLabel(block.type) }}
                            </a>
                            <span v-else :style="typographyStyleFor(block, 'title_font')">{{ block.title || blockTypeLabel(block.type) }}</span>
                          </h4>
                        </div>
                        <span class="text-xs uppercase tracking-[0.2em] opacity-75">{{ blockTypeLabel(block.type) }}</span>
                      </div>

                      <div :class="animationClass(block.data.animation_type)">
                        <div v-if="block.type === 'text'" class="text-sm whitespace-pre-line" :style="typographyStyleFor(block, 'description_font')">
                          {{ block.data.descriptions || 'هنوز متنی وارد نشده' }}
                        </div>

                        <div v-else-if="block.type === 'link' || block.type === 'social'" class="space-y-3">
                          <img v-if="mediaSrc(block.data.image)" :src="mediaSrc(block.data.image)" alt="Block image" class="h-32 w-full rounded-xl object-cover" />
                          <img v-if="iconSrc(block.data.icon)" :src="iconSrc(block.data.icon)" alt="" class="block-icon" />
                        </div>

                        <div v-else-if="block.type === 'map'" class="space-y-2 text-sm">
                          <p :style="typographyStyleFor(block, 'address_font')">{{ block.data.address || 'هنوز آدرسی وارد نشده.' }}</p>
                          <NeshanMap v-if="hasValidCoords(block)" :latitude="block.data.latitude" :longitude="block.data.longitude" :zoom="16" />
                          <p v-else class="text-xs opacity-70">مختصات معتبری وارد نشده.</p>
                          <!-- map link uses the block title as clickable when present (handled in header) -->
                          <p :style="typographyStyleFor(block, 'button_font')">عرض: {{ block.data.latitude ?? '-' }}, طول: {{ block.data.longitude ?? '-' }}</p>
                        </div>

                        <FaqAccordion v-else-if="block.type === 'faq'" :items="block.data.items" :typography="block.data.typography" />

                        <div v-else-if="block.type === 'gallery'" class="grid gap-3 sm:grid-cols-2">
                          <div v-for="item in block.data.items" :key="'gallery-' + item.key" class="overflow-hidden">
                            <img v-if="mediaSrc(item.image)" :src="mediaSrc(item.image)" alt="Gallery image" class="h-32 w-full object-cover rounded-xl" />
                            <div class="p-3 text-sm">
                              <p :style="typographyStyleFor(block, 'caption_font')">{{ item.caption || 'بدون توضیحات' }}</p>
                              <p class="mt-1 text-xs opacity-75">ترتیب: {{ item.sort }}</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </article>
                  </section>
                </div>
              </div>
            </div>
          </div>
        </aside>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineComponent, h, onMounted, reactive, ref } from 'vue';
import axios from 'axios';
import FaqAccordion from './DigitalBusinessCard/FaqAccordion.vue';
import NeshanMap from './DigitalBusinessCard/NeshanMap.vue';
import {
  FONT_OPTIONS,
  ALIGN_OPTIONS,
  getFontFamily,
  getTypographyFieldsByType,
  getTypographyStyle as buildTypographyStyle,
  normalizeTypography,
} from '../utils/digitalBusinessCardTypography';
import {
  normalizeCssColor,
  getColorInputValue,
  getBackgroundOpacity,
  buildBackgroundColorValue,
  formatColorLabel,
} from '../utils/color';

const props = defineProps({
    editorUrl: { type: String, required: true },
    saveUrl: { type: String, required: true },
    uploadUrl: { type: String, required: true },
    createCardUrl: { type: String, required: false, default: '' },
});

const loading = ref(true);
const saving = ref(false);
const uploadingField = ref('');
const errorMessage = ref('');
const flashMessage = ref('');
const draggedBlockIndex = ref(null);
const dropTargetIndex = ref(null);
const card = reactive(blankCard());

const animationOptions = [
    { label: 'None', value: 'none' },
    { label: 'Fade in', value: 'fade-in' },
    { label: 'Slide up', value: 'slide-up' },
    { label: 'Slide down', value: 'slide-down' },
    { label: 'Slide left', value: 'slide-left' },
    { label: 'Slide right', value: 'slide-right' },
    { label: 'Zoom in', value: 'zoom-in' },
    { label: 'Bounce', value: 'bounce' },
];

const blockTypes = [
  { key: 'text', label: 'متن' },
  { key: 'link', label: 'لینک' },
  { key: 'social', label: 'شبکه اجتماعی' },
  { key: 'map', label: 'نقشه' },
  { key: 'faq', label: 'پرسش‌وپاسخ' },
  { key: 'gallery', label: 'گالری' },
];

const fontOptions = FONT_OPTIONS;

function blockTypeLabel(type) {
  const map = {
    text: 'متن',
    link: 'لینک',
    social: 'شبکه اجتماعی',
    map: 'نقشه',
    faq: 'سوالات',
    gallery: 'گالری',
  };

  return map[type] || type;
}

  const resolvedHeaderTextColor = computed(() => {
    const value = String(card.header_text_color || '').trim();

    return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(value) ? value : '#ffffff';
  });

const pageBackgroundStyle = computed(() => ({
  backgroundImage: card.page_background_image
    ? `linear-gradient(rgba(15, 23, 42, 0.12), rgba(15, 23, 42, 0.2)), url(${mediaSrc(card.page_background_image)})`
    : 'linear-gradient(180deg, #e2e8f0 0%, #f8fafc 100%)',
  backgroundColor: card.page_background_image ? '#f8fafc' : '#e2e8f0',
  backgroundRepeat: 'no-repeat',
  backgroundSize: 'cover',
  backgroundPosition: 'center',
}));

const headerBackgroundStyle = computed(() => ({
  backgroundImage: card.image_background
    ? `url(${mediaSrc(card.image_background)})`
    : 'linear-gradient(135deg, #0f172a, #334155)',
  backgroundRepeat: 'no-repeat',
  backgroundSize: 'cover',
  backgroundPosition: 'center',
}));

const Field = defineComponent({
    name: 'Field',
    props: {
        label: { type: String, required: true },
        class: { type: String, default: '' },
    },
    setup(fieldProps, { slots }) {
        return () => h(
            'label',
            { class: fieldProps.class ? `block ${fieldProps.class}` : 'block' },
            [
                h('span', { class: 'mb-1 block text-sm font-medium text-slate-700' }, fieldProps.label),
                slots.default ? slots.default() : null,
            ],
        );
    },
});

function blankCard() {
  return { id: null, title: '', slug:'', des: '', logo: '', image_background: '', page_background_image: '', header_text_color: '#ffffff', blocks: [] };
}

function createTempId(prefix) {
    if (window.crypto?.randomUUID) {
        return `${prefix}_${window.crypto.randomUUID()}`;
    }

    return `${prefix}_${Date.now()}_${Math.random().toString(16).slice(2)}`;
}

function createEmptyFaqItem() {
    return { key: createTempId('faq'), temp_id: createTempId('faq'), id: null, question: '', answer: '', sort: 1 };
}

function createEmptyGalleryItem() {
    return { key: createTempId('gallery'), temp_id: createTempId('gallery'), id: null, image: '', caption: '', sort: 1 };
}

function createEmptyBlock(type) {
  const block = {
    key: createTempId('block'),
    temp_id: createTempId('block'),
    id: null,
    type,
    title: '',
    sort: card.blocks.length + 1,
    color: '#0f172a',
    background_color: '#ffffff',
    data: { typography: {} },
  };

  if (type === 'text') block.data = { descriptions: '', typography: {} };
  if (type === 'link') block.data = { link: '', image: '', animation_type: 'none', typography: {} };
  if (type === 'social') block.data = { link: '', icon: '', animation_type: 'none', typography: {} };
  if (type === 'map') block.data = { address: '', link: '', latitude: '', longitude: '', typography: {} };
  if (type === 'faq') block.data = { items: [createEmptyFaqItem()], typography: {} };
  if (type === 'gallery') block.data = { items: [createEmptyGalleryItem()], typography: {} };

    return block;
}

function normalizeCard(payload) {
    card.id = payload.id ?? null;
    card.title = payload.title ?? '';
    card.des = payload.des ?? '';
    card.slug = payload.slug ?? '';
    card.logo = payload.logo ?? '';
    card.image_background = payload.image_background ?? '';
    card.page_background_image = payload.page_background_image ?? '';
    card.header_text_color = payload.header_text_color ?? '#ffffff';
    card.title_font_family = payload.title_font_family ?? '';
    card.description_font_family = payload.description_font_family ?? '';
    card.blocks = (payload.blocks || []).map((block, index) => normalizeBlock(block, index));
    normalizeBlockSorts();
}

function normalizeBlock(block, index) {
    const normalized = {
        key: block.id ? `block-${block.id}` : createTempId('block'),
        temp_id: block.id ? null : (block.temp_id ?? createTempId('block')),
        id: block.id ?? null,
        type: block.type || '',
        title: block.title ?? '',
        sort: block.sort ?? index + 1,
        color: block.color ?? '#0f172a',
        background_color: block.background_color ?? '#ffffff',
        data: {},
    };

    if (normalized.type === 'text') normalized.data = {
      descriptions: block.data?.descriptions ?? '',
      typography: normalizeTypography(block.data?.typography),
    };
    if (normalized.type === 'link') normalized.data = {
      link: block.data?.link ?? '',
      image: block.data?.image ?? '',
      animation_type: block.data?.animation_type ?? 'none',
      typography: normalizeTypography(block.data?.typography),
    };
    if (normalized.type === 'social') normalized.data = {
      link: block.data?.link ?? '',
      icon: block.data?.icon ?? '',
      animation_type: block.data?.animation_type ?? 'none',
      typography: normalizeTypography(block.data?.typography),
    };
    if (normalized.type === 'map') normalized.data = {
      address: block.data?.address ?? '',
      link: block.data?.link ?? '',
      latitude: block.data?.latitude ?? '',
      longitude: block.data?.longitude ?? '',
      typography: normalizeTypography(block.data?.typography),
    };
    if (normalized.type === 'faq') normalized.data = {
      items: normalizeFaqItems(block.data?.items || []),
      typography: normalizeTypography(block.data?.typography),
    };
    if (normalized.type === 'gallery') normalized.data = {
      items: normalizeGalleryItems(block.data?.items || []),
      typography: normalizeTypography(block.data?.typography),
    };

    if ((normalized.type === 'faq' || normalized.type === 'gallery') && normalized.data.items.length === 0) {
        normalized.data.items = normalized.type === 'faq' ? [createEmptyFaqItem()] : [createEmptyGalleryItem()];
    }

    return normalized;
}

function normalizeFaqItems(items) {
    return items.map((item, index) => ({
        key: item.id ? `faq-${item.id}` : createTempId('faq'),
        temp_id: item.id ? null : (item.temp_id ?? createTempId('faq')),
        id: item.id ?? null,
        question: item.question ?? '',
        answer: item.answer ?? '',
        sort: item.sort ?? index + 1,
    }));
}

function normalizeGalleryItems(items) {
    return items.map((item, index) => ({
        key: item.id ? `gallery-${item.id}` : createTempId('gallery'),
        temp_id: item.id ? null : (item.temp_id ?? createTempId('gallery')),
        id: item.id ?? null,
        image: item.image ?? '',
        caption: item.caption ?? '',
        sort: item.sort ?? index + 1,
    }));
}

function normalizeBlockSorts() {
    card.blocks.forEach((block, index) => {
        block.sort = index + 1;
    });
}

function normalizeItemSorts(items) {
    items.forEach((item, index) => {
        item.sort = index + 1;
    });
}

function addBlock(type) {
    card.blocks.push(createEmptyBlock(type));
    normalizeBlockSorts();
}

function removeBlock(index) {
    card.blocks.splice(index, 1);
    normalizeBlockSorts();
}

function startBlockDrag(index) {
    draggedBlockIndex.value = index;
    dropTargetIndex.value = index;
}

function setDropTarget(index) {
    dropTargetIndex.value = index;
}

function dropBlock(index) {
    if (draggedBlockIndex.value === null || draggedBlockIndex.value === index) {
        endBlockDrag();
        return;
    }

    const blocks = [...card.blocks];
    const [moved] = blocks.splice(draggedBlockIndex.value, 1);
    blocks.splice(index, 0, moved);
    card.blocks = blocks;
    normalizeBlockSorts();
    endBlockDrag();
}

function endBlockDrag() {
    draggedBlockIndex.value = null;
    dropTargetIndex.value = null;
}

function addFaqItem(block) {
    block.data.items.push(createEmptyFaqItem());
    normalizeItemSorts(block.data.items);
}

function removeFaqItem(block, index) {
    if (block.data.items.length === 1) {
        return;
    }

    block.data.items.splice(index, 1);
    normalizeItemSorts(block.data.items);
}

function addGalleryItem(block) {
    block.data.items.push(createEmptyGalleryItem());
    normalizeItemSorts(block.data.items);
}

function removeGalleryItem(block, index) {
    if (block.data.items.length === 1) {
        return;
    }

    block.data.items.splice(index, 1);
    normalizeItemSorts(block.data.items);
}

function serializeBlock(block, index) {
    return {
        id: block.id ?? null,
        temp_id: block.id ? null : (block.temp_id ?? createTempId('block')),
        type: String(block.type || ''),
        title: String(block.title || ''),
        sort: index + 1,
        color: String(block.color || '#0f172a'),
        background_color: String(block.background_color || '#ffffff'),
        data: serializeBlockData(block),
    };
}

function serializeBlockData(block) {
  const typography = normalizeTypography(block?.data?.typography);

    if (block.type === 'faq') {
        normalizeItemSorts(block.data.items);

        return {
      typography,
            items: block.data.items.map((item, index) => ({
                id: item.id ?? null,
                temp_id: item.id ? null : (item.temp_id ?? createTempId('faq')),
                question: String(item.question || ''),
                answer: String(item.answer || ''),
                sort: index + 1,
            })),
        };
    }

    if (block.type === 'gallery') {
        normalizeItemSorts(block.data.items);

        return {
        typography,
            items: block.data.items.map((item, index) => ({
                id: item.id ?? null,
                temp_id: item.id ? null : (item.temp_id ?? createTempId('gallery')),
                image: String(item.image || ''),
                caption: String(item.caption || ''),
                sort: index + 1,
            })),
        };
    }

    if (block.type === 'link' || block.type === 'social') {
        return {
            ...block.data,
            animation_type: block.data.animation_type || 'none',
        typography,
        };
    }

    return {
      ...block.data,
      typography,
    };
}

  function typographyFieldsForBlock(block) {
    return getTypographyFieldsByType(block?.type);
  }

  function typographyStyleFor(block, key) {
    return buildTypographyStyle(block?.data?.typography, key);
  }

  const getCardTitleStyle = computed(() => {
    const fontFamily = getFontFamily(card.title_font_family);
    if (!fontFamily) {
      return {};
    }
    return { fontFamily };
  });

  const getCardDescriptionStyle = computed(() => {
    const fontFamily = getFontFamily(card.description_font_family);
    if (!fontFamily) {
      return {};
    }
    return { fontFamily };
  });

function buildPayload() {
    normalizeBlockSorts();

    return {
        title: String(card.title || ''),
        slug: card.slug ?? '',
        des: card.des ?? '',
        logo: card.logo ?? '',
        image_background: card.image_background ?? '',
        page_background_image: card.page_background_image ?? '',
        header_text_color: resolvedHeaderTextColor.value,
        title_font_family: card.title_font_family ?? '',
        description_font_family: card.description_font_family ?? '',
        blocks: card.blocks.map((block, index) => serializeBlock(block, index)),
    };
}

async function loadEditor() {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get(props.editorUrl);
        normalizeCard(response.data.data);
    } catch (error) {
        errorMessage.value = extractErrorMessage(error, 'Unable to load editor data.');
    } finally {
        loading.value = false;
    }
}

async function saveCard() {
    saving.value = true;
    errorMessage.value = '';
    flashMessage.value = '';

    try {
        const response = await axios.post(props.saveUrl, buildPayload());
        normalizeCard(response.data.data);
        flashMessage.value = response.data.message || 'Saved successfully.';
    } catch (error) {
        errorMessage.value = extractErrorMessage(error, 'Unable to save editor data.');
    } finally {
        saving.value = false;
    }
}

async function uploadMedia(field, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploadingField.value = field;
    errorMessage.value = '';
    flashMessage.value = '';

    try {
        const formData = new FormData();
        formData.append('field', field);
        formData.append('file', file);

        const response = await axios.post(props.uploadUrl, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (field === 'logo' || field === 'image_background' || field === 'page_background_image') {
            card[field] = response.data.data.path || response.data.data.url || '';
        }

        flashMessage.value = response.data.message || 'با موفقیت آپلود شد';
    } catch (error) {
        errorMessage.value = extractErrorMessage(error, 'خطا در لود فایل');
    } finally {
        uploadingField.value = '';
        event.target.value = '';
    }
}

  function removeBlockImage(block) {
    if (block && block.data) {
      block.data.image = '';
    }
  }
  function removeBlockIcon(block) {
    if (block && block.data) {
      block.data.icon = '';
    }
  }
  async function uploadBlockIcon(block, event) {
    const file = event.target.files?.[0];

      if (!file) {
          return;
      }

      uploadingField.value = `icon-${block.key}`;
      errorMessage.value = '';
      flashMessage.value = '';

      try {
          const formData = new FormData();
          formData.append('field', 'block_icon');
          formData.append('file', file);

          const response = await axios.post(props.uploadUrl, formData, {
              headers: { 'Content-Type': 'multipart/form-data' },
          });

          block.data.icon = response.data.data.path || response.data.data.url || '';
          flashMessage.value = response.data.message || 'آیکون با موفقیت آپلود شد';
      } catch (error) {
          errorMessage.value = extractErrorMessage(error, 'خطا در آپلود آیکون');
      } finally {
          uploadingField.value = '';
          event.target.value = '';
      }
  }

  function hasValidCoords(block) {
    const lat = Number(block?.data?.latitude);
    const lng = Number(block?.data?.longitude);
    return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat) <= 90 && Math.abs(lng) <= 180;
  }

  function getNeshanMapUrl(lat, lng) {
    const la = Number(lat);
    const ln = Number(lng);
    if (!Number.isFinite(la) || !Number.isFinite(ln)) return null;
    return `https://neshan.org/maps/@${la},${ln},16z`;
  }

  function previewBlockLink(block) {
    if (block?.type === 'map') {
      return getNeshanMapUrl(block?.data?.latitude, block?.data?.longitude);
    }

    return block?.data?.link || null;
  }

async function uploadBlockImage(block, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploadingField.value = `block-${block.key}`;

    try {
        const formData = new FormData();
        formData.append('field', 'block_image');
        formData.append('file', file);

        const response = await axios.post(props.uploadUrl, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        block.data.image = response.data.data.path || response.data.data.url || '';
    } catch (error) {
        errorMessage.value = extractErrorMessage(error, 'Unable to upload block image.');
    } finally {
        uploadingField.value = '';
        event.target.value = '';
    }
}

async function uploadGalleryImage(block, item, event) {
    const file = event.target.files?.[0];

    if (!file) {
        return;
    }

    uploadingField.value = `gallery-${block.key}-${item.key}`;

    try {
        const formData = new FormData();
        formData.append('field', 'gallery_image');
        formData.append('file', file);

        const response = await axios.post(props.uploadUrl, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        item.image = response.data.data.path || response.data.data.url || '';
    } catch (error) {
        errorMessage.value = extractErrorMessage(error, 'Unable to upload gallery image.');
    } finally {
        uploadingField.value = '';
        event.target.value = '';
    }
}

function goToCreateCard() {
    window.location.href = props.createCardUrl;
}

function updateBlockBackgroundColor(block, hexColor, opacity) {
    if (!block) return

    block.background_color = buildBackgroundColorValue(hexColor, opacity)
}

function extractErrorMessage(error, fallback) {
    if (error?.response?.data?.message) {
        return error.response.data.message;
    }

    const validationErrors = error?.response?.data?.errors;

    if (validationErrors) {
        return Object.values(validationErrors).flat().join(' ');
    }

    return fallback;
}

function mediaSrc(value) {
    if (!value) {
        return '';
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('data:') || value.startsWith('/')) {
        return value;
    }

    return `/storage/${value.replace(/^\/+/, '')}`;
}


function iconSrc(value) {
    if (!value || typeof value !== 'string') {
        return '';
    }

    const trimmedValue = value.trim();

    if (!trimmedValue) {
        return '';
    }

    const looksLikeImage = /\.(png|jpe?g|webp|gif|svg)(\?.*)?$/i.test(trimmedValue)
        || trimmedValue.startsWith('/storage/')
        || /^https?:\/\//i.test(trimmedValue)
        || trimmedValue.startsWith('data:image/');

    if (!looksLikeImage) {
        return '';
    }

    if (/^https?:\/\//i.test(trimmedValue) || trimmedValue.startsWith('/') || trimmedValue.startsWith('data:image/')) {
        return trimmedValue;
    }

    return `/storage/${trimmedValue.replace(/^public\//, '').replace(/^\/+/, '')}`;
}

function blockCardStyle() {
    return {
    borderColor: '#cbd5e1',
    boxShadow: 'none',
    };
}

function previewBlockStyle(block) {
  return {
    color: block.color || '#0f172a',
    backgroundColor: normalizeCssColor(block.background_color) || '#ffffff',
    borderColor: 'rgba(148, 163, 184, 0.55)',
  };
}

function animationClass(animationType) {
    if (!animationType || animationType === 'none') {
        return '';
    }

    return `anim-${animationType}`;
}

onMounted(loadEditor);
</script>

<style scoped>
.digital-card-preview {
  font-family: 'LoopCardFont', sans-serif;
}

.mobile-preview-content {
  font-family: 'LoopCardFont', sans-serif !important;
}

/* RTL preview: ensure block content is right-aligned for Persian */
.mobile-preview-content, .preview-blocks {
  direction: rtl;
  text-align: right;
}

.preview-header-content {
  background: transparent;
  backdrop-filter: none;
  border: 0;
  box-shadow: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  width: auto;
  max-width: 100%;
  text-align: center;
}

 
@font-face {
  font-family: 'LoopCardFont';
  src: url('/assets/fonts/vazir/UI-Farsi-Digits-Non-Latin/fonts/webfonts/Vazirmatn-UI-FD-NL-Regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
@font-face {
    font-family: 'VazirFont';
    src: url('/assets/fonts/vazir/Vazir.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'IRANSansFont';
    src: url('/assets/fonts/IRANSans/IRANSansWeb(FaNum).woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'YekanFont';
    src: url('/assets/fonts/Yekan/Yekan.woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'TanhaFont';
    src: url('/assets/fonts/Tanha/Tanha-FD.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'BSinaBd';
    src: url('/assets/fonts/businesscard/BSinaBd.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'KoodakB';
    src: url('/assets/fonts/businesscard/KoodakB.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}
@font-face {
    font-family: 'Khodkar';
    src: url('/assets/fonts/businesscard/Khodkar.ttf') format('truetype');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

.editor-input,
.editor-file {
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid rgb(203 213 225);
    background: white;
    padding: 0.75rem 0.875rem;
    font-size: 0.95rem;
    color: rgb(15 23 42);
    outline: none;
}

.editor-file {
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
}

.color-input {
    height: 2.75rem;
    width: 4rem;
    border: 1px solid rgb(203 213 225);
    border-radius: 0.75rem;
    background: white;
    padding: 0.2rem;
}

.editor-input:focus,
.editor-file:focus,
.color-input:focus {
    border-color: rgb(15 23 42);
    box-shadow: 0 0 0 3px rgb(15 23 42 / 0.08);
}

.add-block-btn,
.add-item-btn,
.danger-btn,
.preview-link,
.drag-handle {
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.15s ease;
}

.drag-handle {
    cursor: grab;
    user-select: none;
    padding: 0.25rem 0.5rem;
}

.drag-handle:active {
    cursor: grabbing;
}

.no-drag {
    /* Prevent drag initiation on interactive elements */
}

.add-block-btn,
.add-item-btn {
    background: rgb(226 232 240);
    color: rgb(15 23 42);
    padding: 0.5rem 0.875rem;
}

.add-block-btn:hover,
.add-item-btn:hover {
    background: rgb(203 213 225);
}

.danger-btn {
    background: rgb(254 226 226);
    color: rgb(153 27 27);
    padding: 0.5rem 0.875rem;
}

.danger-btn:hover:not(:disabled) {
    background: rgb(252 165 165);
}

.danger-btn:disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.preview-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
  background: rgb(15 23 42 / 0.08);
  color: inherit;
    padding: 0.55rem 0.9rem;
  border: 1px solid rgb(148 163 184 / 0.35);
}

.preview-link:hover {
  background: rgb(15 23 42 / 0.14);
}

.block-icon,
.block-icon-preview {
  width: 2rem;
  height: 2rem;
  object-fit: contain;
  flex-shrink: 0;
}

.block-icon-preview {
  width: 3rem;
  height: 3rem;
}

.drag-handle {
    cursor: grab;
    padding: 0.45rem 0.65rem;
    background: rgb(226 232 240);
    color: rgb(71 85 105);
    user-select: none;
}

.drag-handle:active {
    cursor: grabbing;
}

.block-card,
.block-preview {
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.block-card.is-dragging,
.block-preview.is-dragging {
    opacity: 0.65;
    transform: scale(0.99);
}

.block-card.is-drop-target,
.block-preview.is-drop-target {
    box-shadow: 0 0 0 2px rgb(148 163 184);
}

.mobile-preview-frame {
  position: relative;
  width: min(100%, 410px);
  border-radius: 2.5rem;
  background: linear-gradient(180deg, rgb(15 23 42), rgb(30 41 59));
  padding: 0.9rem;
  box-shadow: 0 30px 80px rgb(15 23 42 / 0.24);
}

.mobile-preview-notch {
  position: absolute;
  left: 50%;
  top: 0.55rem;
  z-index: 2;
  height: 0.7rem;
  width: 7.5rem;
  transform: translateX(-50%);
  border-radius: 9999px;
  background: rgb(15 23 42);
}

.mobile-preview-screen {
  height: 760px;
  overflow-y: auto;
  border-radius: 2rem;
  background-color: rgb(248 250 252);
  background-repeat: no-repeat;
}

.mobile-preview-content {
  min-height: 100%;
}

.preview-header {
  min-height: 240px;
  display: flex;
  justify-content: center;
  align-items: flex-end;
  padding: 1rem;
  background-repeat: no-repeat;
  background-size: cover;
  background-position: center;
  background-color: transparent;
}

.preview-header-content {
  text-align: center;
}

.preview-blocks {
  padding-bottom: 1.25rem;
}

.anim-fade-in {
    animation: fadeIn 0.45s ease both;
}

.anim-slide-up {
    animation: slideUp 0.45s ease both;
}

.anim-slide-down {
    animation: slideDown 0.45s ease both;
}

.anim-slide-left {
    animation: slideLeft 0.45s ease both;
}

.anim-slide-right {
    animation: slideRight 0.45s ease both;
}

.anim-zoom-in {
    animation: zoomIn 0.45s ease both;
}

.anim-bounce {
    animation: bounce 0.6s ease both;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-16px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes slideLeft {
    from { opacity: 0; transform: translateX(16px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes slideRight {
    from { opacity: 0; transform: translateX(-16px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.94); }
    to { opacity: 1; transform: scale(1); }
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-6px); }
    60% { transform: translateY(-3px); }
}
</style>
