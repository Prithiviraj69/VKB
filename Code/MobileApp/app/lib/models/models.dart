class User {
  final String id;
  final String mobile;
  final String? role;

  User({required this.id, required this.mobile, this.role});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id']?.toString() ?? '',
      mobile: json['mobile'] ?? '',
      role: json['role'],
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'mobile': mobile,
        'role': role,
      };
}

class WorkerFamilyMember {
  final int? id;
  final String? workerId;
  final String name;
  final String relation;
  final String gender;
  final int? age;
  final String? dob;

  WorkerFamilyMember({
    this.id,
    this.workerId,
    required this.name,
    required this.relation,
    required this.gender,
    this.age,
    this.dob,
  });

  factory WorkerFamilyMember.fromJson(Map<String, dynamic> json) {
    return WorkerFamilyMember(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? ''),
      workerId: json['worker_id'],
      name: json['name'] ?? '',
      relation: json['relation'] ?? '',
      gender: json['gender'] ?? '',
      age: json['age'] is int ? json['age'] : int.tryParse(json['age']?.toString() ?? ''),
      dob: json['dob'],
    );
  }

  Map<String, dynamic> toJson() => {
        if (id != null) 'id': id,
        if (workerId != null) 'worker_id': workerId,
        'name': name,
        'relation': relation,
        'gender': gender,
        if (age != null) 'age': age,
        if (dob != null) 'dob': dob,
      };
}

class Worker {
  final String id;
  final String userId;
  final String workerCode;
  final String fullName;
  final String dob;
  final String gender;
  final String? category;
  final String? aadhaarNumber;
  final String? panNumber;
  final String? mobileAlt;
  final String? email;
  final String? fatherName;
  final String? education;
  final String? educationStatus;
  final String? maritalStatus;
  final int profileScore;
  
  // New manual entry / Aadhaar autofill / image / system generated fields
  final String? aadhaarAddress;
  final String? rationCardNumber;
  final String? localAddress;
  final String? identificationMark;
  final String? jobProfile;
  final String? employerName;
  final String? employmentSite;
  final String? bankAccountNumber;
  final String? bankNameBranch;
  final String? bankIfsc;
  final String? signatureUrl;
  final String? photoUrl;
  
  final String? registrationNo;
  final String? dateFirstSubscription;
  final String? dateLastSubscription;
  final int installmentsPaidCount;
  final int installmentsPaidTotal;
  final bool isMembershipRevived;
  final String? revivalPeriod;

  // Additional fields
  final String? employmentStatus;
  final bool isBocwRegistered;
  final String? bocwRegNo;
  final String? bocwStateBoard;
  final String? bocwRegDate;
  final String? bocwValidUpto;
  
  final String? businessName;
  final String? businessAddress;
  final String? businessCategory;
  
  final List<WorkerFamilyMember> familyMembers;

  Worker({
    required this.id,
    required this.userId,
    required this.workerCode,
    required this.fullName,
    required this.dob,
    required this.gender,
    this.category,
    this.aadhaarNumber,
    this.panNumber,
    this.mobileAlt,
    this.email,
    this.fatherName,
    this.education,
    this.educationStatus,
    this.maritalStatus,
    required this.profileScore,
    
    this.aadhaarAddress,
    this.rationCardNumber,
    this.localAddress,
    this.identificationMark,
    this.jobProfile,
    this.employerName,
    this.employmentSite,
    this.bankAccountNumber,
    this.bankNameBranch,
    this.bankIfsc,
    this.signatureUrl,
    this.photoUrl,
    
    this.registrationNo,
    this.dateFirstSubscription,
    this.dateLastSubscription,
    this.installmentsPaidCount = 0,
    this.installmentsPaidTotal = 0,
    this.isMembershipRevived = false,
    this.revivalPeriod,

    this.employmentStatus,
    this.isBocwRegistered = false,
    this.bocwRegNo,
    this.bocwStateBoard,
    this.bocwRegDate,
    this.bocwValidUpto,
    this.businessName,
    this.businessAddress,
    this.businessCategory,
    
    this.familyMembers = const [],
  });

  factory Worker.fromJson(Map<String, dynamic> json) {
    var familyList = <WorkerFamilyMember>[];
    if (json['family_members'] != null) {
      familyList = (json['family_members'] as List)
          .map((i) => WorkerFamilyMember.fromJson(i))
          .toList();
    }
    
    return Worker(
      id: json['id']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      workerCode: json['worker_code'] ?? '',
      fullName: json['full_name'] ?? '',
      dob: json['dob'] ?? '',
      gender: json['gender'] ?? 'male',
      category: json['category'],
      aadhaarNumber: json['aadhaar_number'],
      panNumber: json['pan_number'],
      mobileAlt: json['mobile_alt'],
      email: json['email'],
      fatherName: json['father_name'],
      education: json['education'],
      educationStatus: json['education_status'],
      maritalStatus: json['marital_status'],
      profileScore: json['profile_score'] ?? 0,
      
      aadhaarAddress: json['aadhaar_address'],
      rationCardNumber: json['ration_card_number'],
      localAddress: json['local_address'],
      identificationMark: json['identification_mark'],
      jobProfile: json['job_profile'],
      employerName: json['employer_name'],
      employmentSite: json['employment_site'],
      bankAccountNumber: json['bank_account_number'],
      bankNameBranch: json['bank_name_branch'],
      bankIfsc: json['bank_ifsc'],
      signatureUrl: json['signature_url'],
      photoUrl: json['photo_url'],
      
      registrationNo: json['registration_no'],
      dateFirstSubscription: json['date_first_subscription'],
      dateLastSubscription: json['date_last_subscription'],
      installmentsPaidCount: json['installments_paid_count'] ?? 0,
      installmentsPaidTotal: json['installments_paid_total'] ?? 0,
      isMembershipRevived: json['is_membership_revived'] == 1 || json['is_membership_revived'] == true,
      revivalPeriod: json['revival_period'],

      employmentStatus: json['employment_status'],
      isBocwRegistered: json['is_bocw_registered'] == 1 || json['is_bocw_registered'] == true || json['is_bocw_registered']?.toString() == '1',
      bocwRegNo: json['bocw_reg_no'],
      bocwStateBoard: json['bocw_state_board'],
      bocwRegDate: json['bocw_reg_date'],
      bocwValidUpto: json['bocw_valid_upto'],
      businessName: json['business_name'],
      businessAddress: json['business_address'],
      businessCategory: json['business_category'],
      
      familyMembers: familyList,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'user_id': userId,
        'worker_code': workerCode,
        'full_name': fullName,
        'dob': dob,
        'gender': gender,
        'category': category,
        'aadhaar_number': aadhaarNumber,
        'pan_number': panNumber,
        'mobile_alt': mobileAlt,
        'email': email,
        'father_name': fatherName,
        'education': education,
        'education_status': educationStatus,
        'marital_status': maritalStatus,
        'profile_score': profileScore,
        
        'aadhaar_address': aadhaarAddress,
        'ration_card_number': rationCardNumber,
        'local_address': localAddress,
        'identification_mark': identificationMark,
        'job_profile': jobProfile,
        'employer_name': employerName,
        'employment_site': employmentSite,
        'bank_account_number': bankAccountNumber,
        'bank_name_branch': bankNameBranch,
        'bank_ifsc': bankIfsc,
        'signature_url': signatureUrl,
        'photo_url': photoUrl,
        
        'registration_no': registrationNo,
        'date_first_subscription': dateFirstSubscription,
        'date_last_subscription': dateLastSubscription,
        'installments_paid_count': installmentsPaidCount,
        'installments_paid_total': installmentsPaidTotal,
        'is_membership_revived': isMembershipRevived,
        'revival_period': revivalPeriod,

        'employment_status': employmentStatus,
        'is_bocw_registered': isBocwRegistered ? 1 : 0,
        'bocw_reg_no': bocwRegNo,
        'bocw_state_board': bocwStateBoard,
        'bocw_reg_date': bocwRegDate,
        'bocw_valid_upto': bocwValidUpto,
        'business_name': businessName,
        'business_address': businessAddress,
        'business_category': businessCategory,
        
        'family': familyMembers.map((m) => m.toJson()).toList(),
      };
}

class Scheme {
  final String id;
  final String name;
  final String? code;
  final String description;
  final String eligibilityCriteria;
  final String benefits;
  final String ministry;
  final String level; // central, state, NGO
  final String? stateName;
  final bool isActive;
  final String? applyUrl;
  final String? creatorName;
  final String? creatorRole;
  final String? status;
  final bool hasApplied;
  final String? applicationStatus;
  final String? departmentName;

  Scheme({
    required this.id,
    required this.name,
    this.code,
    required this.description,
    required this.eligibilityCriteria,
    required this.benefits,
    required this.ministry,
    required this.level,
    this.stateName,
    required this.isActive,
    this.applyUrl,
    this.creatorName,
    this.creatorRole,
    this.status,
    this.hasApplied = false,
    this.applicationStatus,
    this.departmentName,
  });

  factory Scheme.fromJson(Map<String, dynamic> json) {
    return Scheme(
      id: json['id']?.toString() ?? json['_id']?.toString() ?? '',
      name: json['name'] ?? json['title'] ?? '',
      code: json['code'],
      description: json['description'] ?? '',
      eligibilityCriteria: json['eligibility_criteria'] ?? '',
      benefits: json['benefits'] ?? '',
      ministry: json['ministry'] ?? '',
      level: json['level'] ?? 'central',
      stateName: json['state_name'],
      isActive: json['is_active'] == 1 || json['is_active'] == true,
      applyUrl: json['apply_url'] ?? json['url'],
      creatorName: json['creator_name'],
      creatorRole: json['creator_role'],
      status: json['status'] ?? 'pending',
      hasApplied: json['has_applied'] == true,
      applicationStatus: json['application_status'],
      departmentName: json['department_name'],
    );
  }
}

class SchemeApplication {
  final String id;
  final String applicationNumber;
  final String workerId;
  final String schemeId;
  final String status; // pending, approved, rejected, applied
  final String appliedAt;
  final Map<String, dynamic>? submittedData;
  final Scheme? scheme;
  final Map<String, dynamic>? workerDetails;

  SchemeApplication({
    required this.id,
    required this.applicationNumber,
    required this.workerId,
    required this.schemeId,
    required this.status,
    required this.appliedAt,
    this.submittedData,
    this.scheme,
    this.workerDetails,
  });

  factory SchemeApplication.fromJson(Map<String, dynamic> json) {
    return SchemeApplication(
      id: json['id']?.toString() ?? json['_id']?.toString() ?? '',
      applicationNumber: json['application_number'] ?? '',
      workerId: json['worker_id']?.toString() ?? '',
      schemeId: json['scheme_id']?.toString() ?? '',
      status: json['status'] ?? 'pending',
      appliedAt: json['applied_at'] ?? '',
      submittedData: json['submitted_data'] is Map ? Map<String, dynamic>.from(json['submitted_data']) : null,
      scheme: json['scheme'] != null ? Scheme.fromJson(json['scheme']) : null,
      workerDetails: json['worker'] is Map ? Map<String, dynamic>.from(json['worker']) : null,
    );
  }
}

class Grievance {
  final int id;
  final String ticketNumber;
  final int workerId;
  final String subject;
  final String description;
  final String category;
  final String status; // open, in_progress, resolved, closed
  final String? resolution;
  final String createdAt;

  Grievance({
    required this.id,
    required this.ticketNumber,
    required this.workerId,
    required this.subject,
    required this.description,
    required this.category,
    required this.status,
    this.resolution,
    required this.createdAt,
  });

  factory Grievance.fromJson(Map<String, dynamic> json) {
    return Grievance(
      id: json['id'],
      ticketNumber: json['ticket_number'] ?? '',
      workerId: json['worker_id'] ?? 0,
      subject: json['subject'] ?? '',
      description: json['description'] ?? '',
      category: json['category'] ?? '',
      status: json['status'] ?? 'open',
      resolution: json['resolution'],
      createdAt: json['created_at'] ?? '',
    );
  }
}

class WorkerDocument {
  final int id;
  final int workerId;
  final String documentType; // aadhaar, pan, photo, certificate, other
  final String documentNumber;
  final String filePath;
  final String status; // pending, verified, rejected
  final String createdAt;

  WorkerDocument({
    required this.id,
    required this.workerId,
    required this.documentType,
    required this.documentNumber,
    required this.filePath,
    required this.status,
    required this.createdAt,
  });

  factory WorkerDocument.fromJson(Map<String, dynamic> json) {
    return WorkerDocument(
      id: json['id'],
      workerId: json['worker_id'] ?? 0,
      documentType: json['document_type'] ?? '',
      documentNumber: json['document_number'] ?? '',
      filePath: json['file_path'] ?? '',
      status: json['status'] ?? 'pending',
      createdAt: json['created_at'] ?? '',
    );
  }
}

class AppNotification {
  final int id;
  final String title;
  final String message;
  final bool isRead;
  final String createdAt;

  AppNotification({
    required this.id,
    required this.title,
    required this.message,
    required this.isRead,
    required this.createdAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'],
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      isRead: json['is_read'] == 1 || json['is_read'] == true,
      createdAt: json['created_at'] ?? '',
    );
  }
}

class StateModel {
  final int id;
  final String name;
  final String code;

  StateModel({required this.id, required this.name, required this.code});

  factory StateModel.fromJson(Map<String, dynamic> json) {
    return StateModel(
      id: json['id'],
      name: json['name'] ?? '',
      code: json['code'] ?? '',
    );
  }
}

class DistrictModel {
  final int id;
  final int stateId;
  final String name;

  DistrictModel({required this.id, required this.stateId, required this.name});

  factory DistrictModel.fromJson(Map<String, dynamic> json) {
    return DistrictModel(
      id: json['id'],
      stateId: json['state_id'] ?? 0,
      name: json['name'] ?? '',
    );
  }
}

class Trade {
  final dynamic id;
  final String name;
  final String code;

  Trade({required this.id, required this.name, required this.code});

  factory Trade.fromJson(Map<String, dynamic> json) {
    return Trade(
      id: json['id'],
      name: json['name'] ?? '',
      code: json['code'] ?? '',
    );
  }
}
