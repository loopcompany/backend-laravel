<div class="card-header bg-light border-bottom px-lg-5 mb-5">
	<!-- Step Buttons START -->
	<div class="bs-stepper-header" role="tablist">
		<!-- Step 1 -->
		<div class="step @if($step>0) active @endif">
													<div class="d-grid text-center align-items-center">
														<button type="button" class="btn btn-link step-trigger mb-0"
															role="tab" id="steppertrigger1" aria-controls="step-1"
															aria-selected="true">
															<span class="bs-stepper-circle">1</span>
														</button>
														<p>زمان و مکان</p>
													</div>
		</div>
		<div class="line" style="flex: 1 0 32px !important; min-width: 1px !important; min-height: 1px !important; margin: auto !important; background-color: rgba(0, 0, 0, .12) !important;"></div>

		<!-- Step 2 -->
		<div class="step @if($step>1) active @endif">
													<div class="d-grid text-center align-items-center">
														<button type="button" class="btn btn-link step-trigger mb-0"
															role="tab" id="steppertrigger2" aria-controls="step-2"
															aria-selected="true">
															<span class="bs-stepper-circle">2</span>
														</button>
														<p>جزئیات خدمت</p>
													</div>
		</div>
		<div class="line" style="flex: 1 0 32px !important;
													min-width: 1px !important;
													min-height: 1px !important;
													margin: auto !important;
													background-color: rgba(0, 0, 0, .12) !important;"></div>

		<!-- Step 3 -->
		<div class="step @if($step>2 && !$hasMoreSteps) active @endif">
													<div class="d-grid text-center align-items-center">
														<button type="button" class="btn btn-link step-trigger mb-0"
															role="tab" id="steppertrigger3" aria-controls="step-3"
															aria-selected="true">
															<span class="bs-stepper-circle">3</span>
														</button>
														<p>ثبت نهایی</p>
													</div>
		</div>
	</div>
	<!-- Step Buttons END -->
</div>